<?php

declare(strict_types=1);

use App\Controllers\Admin\CategoryAdminController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PostAdminController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Core\Router;

return static function (Router $router): void {
    // Публичные маршруты
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/category/{slug}', [CategoryController::class, 'show']);
    $router->get('/post/{slug}', [PostController::class, 'show']);

    // Панель управления (Дашборд)
    $router->get('/admin', [DashboardController::class, 'index']);

    // Управление категориями
    $router->get('/admin/categories', [CategoryAdminController::class, 'index']);
    $router->get('/admin/categories/create', [CategoryAdminController::class, 'create']);
    $router->post('/admin/categories/create', [CategoryAdminController::class, 'create']);
    $router->get('/admin/categories/edit/{id}', [CategoryAdminController::class, 'edit']);
    $router->post('/admin/categories/edit/{id}', [CategoryAdminController::class, 'edit']);
    $router->get('/admin/categories/delete/{id}', [CategoryAdminController::class, 'delete']);

    // Управление статьями
    $router->get('/admin/posts', [PostAdminController::class, 'index']);
    $router->get('/admin/posts/create', [PostAdminController::class, 'create']);
    $router->post('/admin/posts/create', [PostAdminController::class, 'create']);
    $router->get('/admin/posts/edit/{id}', [PostAdminController::class, 'edit']);
    $router->post('/admin/posts/edit/{id}', [PostAdminController::class, 'edit']);
    $router->get('/admin/posts/delete/{id}', [PostAdminController::class, 'delete']);
};
