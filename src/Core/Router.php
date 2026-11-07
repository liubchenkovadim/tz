<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, array|callable $handler): void
    {
        // Normalize path
        $normalized = '/' . trim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        // Convert {param} placeholders into named regex groups
        $pattern = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '(?P<$1>[^/]+)', $normalized);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                // Extract only named parameters
                $params = array_filter($matches, static fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    return $controller->$action($request, $params);
                }

                if (is_callable($handler)) {
                    return $handler($request, $params);
                }
            }
        }

        // 404 Not Found
        $html = View::render('404.tpl', [
            'pageTitle' => 'Страница не найдена — 404',
            'requestedPath' => $path,
        ]);

        return new Response($html, 404);
    }
}
