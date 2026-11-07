<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class DashboardController
{
    private PostRepository $postRepo;
    private CategoryRepository $categoryRepo;

    public function __construct(
        ?PostRepository $postRepo = null,
        ?CategoryRepository $categoryRepo = null
    ) {
        $this->postRepo = $postRepo ?? new PostRepository();
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
    }

    public function index(Request $request): Response
    {
        $totalPosts = $this->postRepo->getTotalPostsCount();
        $totalCategories = $this->categoryRepo->getTotalCategoriesCount();
        $totalViews = $this->postRepo->getTotalViewsCount();

        // Top 10 most viewed posts
        $topPosts = $this->postRepo->getTopViewed(10);

        $html = View::render('admin/dashboard.tpl', [
            'pageTitle'       => 'Панель управления — Дашборд',
            'totalPosts'      => $totalPosts,
            'totalCategories' => $totalCategories,
            'totalViews'      => $totalViews,
            'topPosts'        => $topPosts,
        ]);

        return new Response($html);
    }
}
