<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    public function run(): void
    {
        $this->configureEnvironment();

        $request = new Request();
        $router = new Router();

        // Загрузка маршрутов из файла routes/web.php
        $routesLoader = require dirname(__DIR__, 2) . '/routes/web.php';
        $routesLoader($router);

        $response = $router->dispatch($request);
        $response->send();
    }

    private function configureEnvironment(): void
    {
        $debug = ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'true') === 'true';

        if ($debug) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(0);
        }
    }
}
