<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class PostController
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

    public function show(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $post = $this->postRepo->findBySlug($slug);

        if (!$post) {
            $html = View::render('404.tpl', [
                'pageTitle'     => 'Статья не найдена',
                'navCategories' => $this->categoryRepo->getAll(),
                'message'       => 'Запрашиваемая статья не найдена или была удалена.',
            ]);
            return new Response($html, 404);
        }

        // Increment views count atomically
        $this->postRepo->incrementViews((int)$post['id']);
        $post['views_count'] = (int)$post['views_count'] + 1;

        // Fetch 3 similar posts based on shared categories
        $categoryIds = array_map(static fn($c) => (int)$c['id'], $post['categories'] ?? []);
        $similarPosts = $this->postRepo->getSimilarPosts((int)$post['id'], $categoryIds, 3);

        $html = View::render('post.tpl', [
            'pageTitle'     => $post['title'] . ' — Блог',
            'navCategories' => $this->categoryRepo->getAll(),
            'post'          => $post,
            'similarPosts'  => $similarPosts,
        ]);

        return new Response($html);
    }
}
