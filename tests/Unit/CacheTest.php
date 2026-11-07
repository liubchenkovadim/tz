<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testRememberExecutesCallbackWhenCacheDisabled(): void
    {
        // When REDIS_ENABLED=false (set in phpunit.xml), remember should always execute callback
        $called = false;
        $result = Cache::remember('test_key', 300, function () use (&$called) {
            $called = true;
            return 'computed_value';
        });

        $this->assertTrue($called);
        $this->assertSame('computed_value', $result);
    }

    public function testGetReturnsNullWhenCacheDisabled(): void
    {
        $this->assertNull(Cache::get('any_key'));
    }
}

