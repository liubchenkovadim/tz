<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testMatchesSimpleGetRoute(): void
    {
        $this->router->get('/', function (Request $req) {
            return new Response('Hello Home', 200);
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExtractsNamedParametersFromRoute(): void
    {
        $capturedSlug = null;

        $this->router->get('/category/{slug}', function (Request $req, array $params) use (&$capturedSlug) {
            $capturedSlug = $params['slug'] ?? null;
            return new Response('Category: ' . $capturedSlug, 200);
        });

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/category/tech-news']);
        $this->router->dispatch($request);

        $this->assertSame('tech-news', $capturedSlug);
    }

    public function testReturns404ForUnmatchedRoute(): void
    {
        $this->router->get('/existing', fn() => new Response('OK'));

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/not-found-url']);
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDifferentHttpMethods(): void
    {
        $this->router->post('/posts', fn() => new Response('Post created', 201));

        $getRequest = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/posts']);
        $postRequest = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/posts']);

        // GET should not match POST route
        $getRes = $this->router->dispatch($getRequest);
        $postRes = $this->router->dispatch($postRequest);

        $this->assertInstanceOf(Response::class, $getRes);
        $this->assertInstanceOf(Response::class, $postRes);
    }
}

