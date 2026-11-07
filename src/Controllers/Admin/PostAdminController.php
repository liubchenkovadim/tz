<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

class PostAdminController
{
    private const PER_PAGE = 15;

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
        $page = max(1, $request->getInt('page', 1));
        $totalPosts = $this->postRepo->getTotalPostsCount();
        $totalPages = max(1, (int)ceil($totalPosts / self::PER_PAGE));

        $posts = $this->postRepo->getAdminPaginated($page, self::PER_PAGE);

        $html = View::render('admin/posts/index.tpl', [
            'pageTitle'   => 'Управление статьями — Админка',
            'posts'       => $posts,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'totalPosts'  => $totalPosts,
        ]);

        return new Response($html);
    }

    public function create(Request $request): Response
    {
        $categories = $this->categoryRepo->getAll();
        $error = null;

        if ($request->getMethod() === 'POST') {
            $title = $request->getString('title');
            $slug = $request->getString('slug');
            $description = $request->getString('description');
            $content = $request->get('content', '');
            $imageUrl = $request->getString('image_url');
            $viewsCount = $request->getInt('views_count', 0);
            $publishedAt = $request->getString('published_at');
            $selectedCategories = (array)$request->get('category_ids', []);

            if ($title === '' || $description === '' || $content === '') {
                $error = 'Пожалуйста, заполните все обязательные поля (название, описание, текст).';
            } elseif (empty($selectedCategories)) {
                $error = 'Выберите хотя бы одну категорию для статьи.';
            } else {
                if ($slug === '') {
                    $slug = $this->generateSlug($title);
                }

                if ($publishedAt === '') {
                    $publishedAt = date('Y-m-d H:i:s');
                }

                $this->postRepo->create([
                    'title'        => $title,
                    'slug'         => $slug,
                    'description'  => $description,
                    'content'      => $content,
                    'image_url'    => $imageUrl ?: null,
                    'views_count'  => $viewsCount,
                    'published_at' => $publishedAt,
                ], $selectedCategories);

                return Response::redirect('/admin/posts');
            }
        }

        $html = View::render('admin/posts/form.tpl', [
            'pageTitle'          => 'Создание статьи — Админка',
            'isEdit'             => false,
            'post'               => ['views_count' => 0, 'published_at' => date('Y-m-d\TH:i')],
            'categories'         => $categories,
            'selectedCategories' => [],
            'error'              => $error,
        ]);

        return new Response($html);
    }

    public function edit(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $post = $this->postRepo->findById($id);

        if (!$post) {
            return Response::redirect('/admin/posts');
        }

        $categories = $this->categoryRepo->getAll();
        $selectedCategories = array_column($post['categories'] ?? [], 'id');
        $error = null;

        if ($request->getMethod() === 'POST') {
            $title = $request->getString('title');
            $slug = $request->getString('slug');
            $description = $request->getString('description');
            $content = $request->get('content', '');
            $imageUrl = $request->getString('image_url');
            $viewsCount = $request->getInt('views_count', (int)$post['views_count']);
            $publishedAt = $request->getString('published_at');
            $selectedCategories = (array)$request->get('category_ids', []);

            if ($title === '' || $description === '' || $content === '') {
                $error = 'Пожалуйста, заполните все обязательные поля.';
            } elseif (empty($selectedCategories)) {
                $error = 'Выберите хотя бы одну категорию.';
            } else {
                if ($slug === '') {
                    $slug = $this->generateSlug($title);
                }

                if ($publishedAt === '') {
                    $publishedAt = $post['published_at'];
                }

                $this->postRepo->update($id, [
                    'title'        => $title,
                    'slug'         => $slug,
                    'description'  => $description,
                    'content'      => $content,
                    'image_url'    => $imageUrl ?: null,
                    'views_count'  => $viewsCount,
                    'published_at' => $publishedAt,
                ], $selectedCategories);

                return Response::redirect('/admin/posts');
            }
        }

        // Format published_at for HTML datetime-local input
        $formattedDate = date('Y-m-d\TH:i', strtotime($post['published_at']));
        $post['published_at_input'] = $formattedDate;

        $html = View::render('admin/posts/form.tpl', [
            'pageTitle'          => 'Редактирование статьи — Админка',
            'isEdit'             => true,
            'post'               => $post,
            'categories'         => $categories,
            'selectedCategories' => $selectedCategories,
            'error'              => $error,
        ]);

        return new Response($html);
    }

    public function delete(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $this->postRepo->delete($id);
        }

        return Response::redirect('/admin/posts');
    }

    private function generateSlug(string $text): string
    {
        $translit = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string)$translit);
        return trim($slug, '-');
    }
}
