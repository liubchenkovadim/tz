<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Enums\PostSort;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class CategoryController
{
    private const PER_PAGE = 6;

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
        $category = $this->categoryRepo->findBySlug($slug);

        if (!$category) {
            $html = View::render('404.tpl', [
                'pageTitle'     => 'Категория не найдена',
                'navCategories' => $this->categoryRepo->getAll(),
                'message'       => 'Запрашиваемая категория не найдена или была удалена.',
            ]);
            return new Response($html, 404);
        }

        // Sorting
        $sortParam = $request->getString('sort', PostSort::DATE_DESC->value);
        $sort = PostSort::fromString($sortParam);

        // Pagination
        $page = max(1, $request->getInt('page', 1));
        $totalPosts = $this->postRepo->countByCategory((int)$category['id']);
        $totalPages = max(1, (int)ceil($totalPosts / self::PER_PAGE));

        if ($page > $totalPages && $totalPosts > 0) {
            $page = $totalPages;
        }

        $posts = $this->postRepo->getPaginatedByCategory(
            (int)$category['id'],
            $page,
            self::PER_PAGE,
            $sort
        );

        $sortOptions = [
            ['value' => PostSort::DATE_DESC->value,  'label' => PostSort::DATE_DESC->getLabel()],
            ['value' => PostSort::DATE_ASC->value,   'label' => PostSort::DATE_ASC->getLabel()],
            ['value' => PostSort::VIEWS_DESC->value, 'label' => PostSort::VIEWS_DESC->getLabel()],
            ['value' => PostSort::VIEWS_ASC->value,  'label' => PostSort::VIEWS_ASC->getLabel()],
        ];

        $html = View::render('category.tpl', [
            'pageTitle'     => $category['title'] . ' — Блог',
            'navCategories' => $this->categoryRepo->getAll(),
            'category'      => $category,
            'posts'         => $posts,
            'currentSort'   => $sort->value,
            'sortOptions'   => $sortOptions,
            'pagination'    => [
                'currentPage' => $page,
                'totalPages'  => $totalPages,
                'totalPosts'  => $totalPosts,
                'perPage'     => self::PER_PAGE,
                'currentSort' => $sort->value,
                'baseUrl'     => '/category/' . urlencode($category['slug']),
            ],
        ]);

        return new Response($html);
    }
}
