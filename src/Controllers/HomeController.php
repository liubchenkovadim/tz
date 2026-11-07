<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class HomeController
{
    private CategoryRepository $categoryRepo;
    private PostRepository $postRepo;

    public function __construct(
        ?CategoryRepository $categoryRepo = null,
        ?PostRepository $postRepo = null
    ) {
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->postRepo = $postRepo ?? new PostRepository();
    }

    public function index(Request $request): Response
    {
        // Cache home sections in Redis for 300 seconds
        $sections = Cache::remember('home:sections', 300, function () {
            $categoriesWithPosts = $this->categoryRepo->getCategoriesWithPosts();

            $result = [];
            foreach ($categoriesWithPosts as $category) {
                $latestPosts = $this->postRepo->getLatestByCategory((int)$category['id'], 3);
                if (!empty($latestPosts)) {
                    $category['posts'] = $latestPosts;
                    $result[] = $category;
                }
            }
            return $result;
        });

        $allCategories = $this->categoryRepo->getAll();

        $html = View::render('home.tpl', [
            'pageTitle'     => 'Главная — Блог',
            'navCategories' => $allCategories,
            'sections'      => $sections,
        ]);

        return new Response($html);
    }
}
