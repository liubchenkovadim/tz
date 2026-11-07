<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\CategoryRepository;

class CategoryAdminController
{
    private CategoryRepository $categoryRepo;
    private \App\Repositories\PostRepository $postRepo;

    public function __construct(
        ?CategoryRepository $categoryRepo = null,
        ?\App\Repositories\PostRepository $postRepo = null
    ) {
        $this->categoryRepo = $categoryRepo ?? new CategoryRepository();
        $this->postRepo = $postRepo ?? new \App\Repositories\PostRepository();
    }

    public function index(Request $request): Response
    {
        $categories = $this->categoryRepo->getAllWithStats();

        $html = View::render('admin/categories/index.tpl', [
            'pageTitle'  => 'Управление категориями — Админка',
            'categories' => $categories,
        ]);

        return new Response($html);
    }

    public function create(Request $request): Response
    {
        $error = null;

        if ($request->getMethod() === 'POST') {
            $title = $request->getString('title');
            $slug = $request->getString('slug');
            $description = $request->getString('description');

            if ($title === '') {
                $error = 'Название категории обязательно.';
            } else {
                if ($slug === '') {
                    $slug = $this->generateSlug($title);
                }

                $this->categoryRepo->create([
                    'title'       => $title,
                    'slug'        => $slug,
                    'description' => $description,
                ]);

                return Response::redirect('/admin/categories');
            }
        }

        $html = View::render('admin/categories/form.tpl', [
            'pageTitle' => 'Создание категории — Админка',
            'isEdit'    => false,
            'category'  => [],
            'error'     => $error,
        ]);

        return new Response($html);
    }

    public function edit(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        $category = $this->categoryRepo->findById($id);

        if (!$category) {
            return Response::redirect('/admin/categories');
        }

        $error = null;

        if ($request->getMethod() === 'POST') {
            $title = $request->getString('title');
            $slug = $request->getString('slug');
            $description = $request->getString('description');

            if ($title === '') {
                $error = 'Название категории обязательно.';
            } else {
                if ($slug === '') {
                    $slug = $this->generateSlug($title);
                }

                $this->categoryRepo->update($id, [
                    'title'       => $title,
                    'slug'        => $slug,
                    'description' => $description,
                ]);

                return Response::redirect('/admin/categories');
            }
        }

        $postsCount = $this->postRepo->countByCategory($id);

        $html = View::render('admin/categories/form.tpl', [
            'pageTitle'  => 'Редактирование категории — Админка',
            'isEdit'     => true,
            'category'   => $category,
            'postsCount' => $postsCount,
            'error'      => $error,
        ]);

        return new Response($html);
    }

    public function delete(Request $request, array $params): Response
    {
        $id = (int)($params['id'] ?? 0);
        if ($id > 0) {
            $this->categoryRepo->delete($id);
        }

        return Response::redirect('/admin/categories');
    }

    private function generateSlug(string $text): string
    {
        $translit = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', (string)$translit);
        return trim($slug, '-');
    }
}
