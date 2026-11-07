<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;

    public function __construct(?array $get = null, ?array $post = null, ?array $server = null)
    {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $this->post[$key] ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }
        return (int) $value;
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key);
        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Build query string while overriding specific keys (e.g. page or sort)
     */
    public function getQueryString(array $overrides = []): string
    {
        $params = array_merge($this->get, $overrides);
        // remove empty or null values
        $params = array_filter($params, static fn($v) => $v !== null && $v !== '');
        return http_build_query($params);
    }
}

