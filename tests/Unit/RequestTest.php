<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testGetMethodAndPath(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/category/tech-news?sort=views_desc',
        ];

        $request = new Request([], [], $server);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/category/tech-news', $request->getPath());
    }

    public function testGetIntAndGetString(): void
    {
        $get = [
            'page'  => '3',
            'sort'  => 'date_desc',
            'bad'   => 'abc',
        ];

        $request = new Request($get, [], []);

        $this->assertSame(3, $request->getInt('page', 1));
        $this->assertSame(1, $request->getInt('non_existent', 1));
        $this->assertSame(0, $request->getInt('bad', 0));

        $this->assertSame('date_desc', $request->getString('sort'));
        $this->assertSame('default', $request->getString('missing', 'default'));
    }

    public function testGetQueryStringWithOverrides(): void
    {
        $get = [
            'sort' => 'views_desc',
            'page' => '1',
        ];

        $request = new Request($get, [], []);

        // Override page to 2
        $queryString = $request->getQueryString(['page' => 2]);
        $this->assertStringContainsString('sort=views_desc', $queryString);
        $this->assertStringContainsString('page=2', $queryString);
    }
}

