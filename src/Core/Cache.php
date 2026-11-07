<?php

declare(strict_types=1);

namespace App\Core;

use Predis\Client as PredisClient;
use Throwable;

class Cache
{
    private static ?PredisClient $client = null;
    private static ?bool $enabled = null;

    private static function init(): void
    {
        if (self::$enabled !== null) {
            return;
        }

        $isEnabled = ($_ENV['REDIS_ENABLED'] ?? getenv('REDIS_ENABLED') ?: 'true') === 'true';

        if (!$isEnabled) {
            self::$enabled = false;
            return;
        }

        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int)($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379);

        try {
            self::$client = new PredisClient([
                'scheme'             => 'tcp',
                'host'               => $host,
                'port'               => $port,
                'timeout'            => 1.0,
                'read_write_timeout' => 1.0,
            ]);

            // Test connection
            self::$client->connect();
            self::$enabled = true;
        } catch (Throwable) {
            // Graceful fallback if Redis is down or unreachable
            self::$client = null;
            self::$enabled = false;
        }
    }

    public static function isEnabled(): bool
    {
        self::init();
        return (bool)self::$enabled;
    }

    public static function get(string $key): mixed
    {
        self::init();
        if (!self::$enabled || self::$client === null) {
            return null;
        }

        try {
            $value = self::$client->get($key);
            if ($value === null) {
                return null;
            }
            return unserialize($value, ['allowed_classes' => true]);
        } catch (Throwable) {
            return null;
        }
    }

    public static function set(string $key, mixed $value, int $ttl = 300): bool
    {
        self::init();
        if (!self::$enabled || self::$client === null) {
            return false;
        }

        try {
            $serialized = serialize($value);
            self::$client->setex($key, $ttl, $serialized);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function delete(string $key): bool
    {
        self::init();
        if (!self::$enabled || self::$client === null) {
            return false;
        }

        try {
            self::$client->del([$key]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Delete keys matching a pattern, e.g. "blog:*"
     */
    public static function deletePattern(string $pattern): bool
    {
        self::init();
        if (!self::$enabled || self::$client === null) {
            return false;
        }

        try {
            $keys = self::$client->keys($pattern);
            if (!empty($keys)) {
                self::$client->del($keys);
            }
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get from cache or execute callback and cache result
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $callback();
        self::set($key, $result, $ttl);
        return $result;
    }

    public static function flush(): bool
    {
        self::init();
        if (!self::$enabled || self::$client === null) {
            return false;
        }

        try {
            self::$client->flushdb();
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

