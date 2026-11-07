<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class HttpEndpointsTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('APP_TEST_URL') ?: 'http://localhost:8088';
    }

    private function request(string $path): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $status,
            'body'   => (string)$body,
        ];
    }

    public function testHomepageReturns200AndRendersContent(): void
    {
        $res = $this->request('/');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('CleanPHP', $res['body']);
        $this->assertStringContainsString('Все статьи', $res['body']);
    }

    public function testCategoryPageReturns200WithSorting(): void
    {
        $res = $this->request('/category/tech-development');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('Технологии и разработка', $res['body']);
        $this->assertStringContainsString('sort-select', $res['body']);
    }

    public function testCategoryPageSortingViews(): void
    {
        $res = $this->request('/category/tech-development?sort=views_desc');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('views_desc', $res['body']);
    }

    public function testAdminDashboardReturns200WithTop10(): void
    {
        $res = $this->request('/admin');

        $this->assertSame(200, $res['status']);
        $this->assertStringContainsString('Топ-10 самых популярных статей по просмотрам', $res['body']);
        $this->assertStringContainsString('Всего статей', $res['body']);
    }

    public function testAdminCategoriesAndPostsPages(): void
    {
        $catRes = $this->request('/admin/categories');
        $this->assertSame(200, $catRes['status']);
        $this->assertStringContainsString('Список категорий', $catRes['body']);

        $postRes = $this->request('/admin/posts');
        $this->assertSame(200, $postRes['status']);
        $this->assertStringContainsString('Список статей', $postRes['body']);
    }

    public function testNonExistentPageReturns404(): void
    {
        $res = $this->request('/some-totally-non-existent-page-url');

        $this->assertSame(404, $res['status']);
        $this->assertStringContainsString('404', $res['body']);
    }
}
