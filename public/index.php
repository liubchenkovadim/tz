<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\Admin\CategoryAdminController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PostAdminController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Core\Request;
use App\Core\Router;

// Error reporting settings
$debug = ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'true') === 'true';
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

$request = new Request();
$router = new Router();

// Public Frontend Routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/category/{slug}', [CategoryController::class, 'show']);
$router->get('/post/{slug}', [PostController::class, 'show']);

// Admin Panel Routes
$router->get('/admin', [DashboardController::class, 'index']);

// Admin Categories Routes
$router->get('/admin/categories', [CategoryAdminController::class, 'index']);
$router->get('/admin/categories/create', [CategoryAdminController::class, 'create']);
$router->post('/admin/categories/create', [CategoryAdminController::class, 'create']);
$router->get('/admin/categories/edit/{id}', [CategoryAdminController::class, 'edit']);
$router->post('/admin/categories/edit/{id}', [CategoryAdminController::class, 'edit']);
$router->get('/admin/categories/delete/{id}', [CategoryAdminController::class, 'delete']);

// Admin Posts Routes
$router->get('/admin/posts', [PostAdminController::class, 'index']);
$router->get('/admin/posts/create', [PostAdminController::class, 'create']);
$router->post('/admin/posts/create', [PostAdminController::class, 'create']);
$router->get('/admin/posts/edit/{id}', [PostAdminController::class, 'edit']);
$router->post('/admin/posts/edit/{id}', [PostAdminController::class, 'edit']);
$router->get('/admin/posts/delete/{id}', [PostAdminController::class, 'delete']);

try {
    $response = $router->dispatch($request);
    $response->send();
} catch (Throwable $e) {
    http_response_code(500);
    if ($debug) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>500 Internal Error</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1 style="color:#d9534f;">500 Internal Server Error</h1>';
        echo '<p><strong>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></p>';
        echo '<pre style="background:#f8f9fa;padding:1rem;border-radius:4px;overflow:auto;">' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '</body></html>';
    } else {
        echo '<h1>500 Internal Server Error</h1>';
        echo '<p>Произошла непредвиденная ошибка. Пожалуйста, попробуйте позже.</p>';
    }
}
