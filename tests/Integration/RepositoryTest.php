<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Enums\PostSort;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private PDO $db;
    private CategoryRepository $categoryRepo;
    private PostRepository $postRepo;

    protected function setUp(): void
    {
        // In-memory SQLite for rapid integration testing
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // SQLite Schema
        $this->db->exec('
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                content TEXT NOT NULL,
                image_url TEXT,
                views_count INTEGER NOT NULL DEFAULT 0,
                published_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE post_categories (
                post_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                PRIMARY KEY (post_id, category_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );
        ');

        $this->categoryRepo = new CategoryRepository($this->db);
        $this->postRepo = new PostRepository($this->db);
    }

    public function testCategoryCrud(): void
    {
        // 1. Create Category
        $catId = $this->categoryRepo->create([
            'title'       => 'Тестовая категория',
            'slug'        => 'test-category',
            'description' => 'Описание тестовой категории',
        ]);
        $this->assertGreaterThan(0, $catId);

        // 2. Find by Slug
        $found = $this->categoryRepo->findBySlug('test-category');
        $this->assertNotNull($found);
        $this->assertSame('Тестовая категория', $found['title']);

        // 3. Update
        $this->categoryRepo->update($catId, [
            'title'       => 'Обновленная категория',
            'slug'        => 'updated-category',
            'description' => 'Новое описание',
        ]);
        $updated = $this->categoryRepo->findById($catId);
        $this->assertSame('Обновленная категория', $updated['title']);

        // 4. Delete
        $this->categoryRepo->delete($catId);
        $this->assertNull($this->categoryRepo->findById($catId));
    }

    public function testPostCreationWithManyToManyCategories(): void
    {
        // Create 2 categories
        $cat1 = $this->categoryRepo->create(['title' => 'Backend', 'slug' => 'backend']);
        $cat2 = $this->categoryRepo->create(['title' => 'DevOps', 'slug' => 'devops']);

        // Create post linked to both categories
        $postId = $this->postRepo->create([
            'title'        => 'Docker для PHP',
            'slug'         => 'docker-dlya-php',
            'description'  => 'Краткое описание статьи',
            'content'      => '<p>Полный текст</p>',
            'image_url'    => 'https://example.com/img.jpg',
            'views_count'  => 50,
            'published_at' => date('Y-m-d H:i:s'),
        ], [$cat1, $cat2]);

        $this->assertGreaterThan(0, $postId);

        // Fetch post by slug
        $post = $this->postRepo->findBySlug('docker-dlya-php');
        $this->assertNotNull($post);
        $this->assertCount(2, $post['categories']);

        // Check category titles
        $catTitles = array_column($post['categories'], 'title');
        $this->assertContains('Backend', $catTitles);
        $this->assertContains('DevOps', $catTitles);
    }

    public function testViewsCountIncrement(): void
    {
        $catId = $this->categoryRepo->create(['title' => 'PHP', 'slug' => 'php']);
        $postId = $this->postRepo->create([
            'title'        => 'Тест просмотров',
            'slug'         => 'test-views',
            'description'  => 'Описание',
            'content'      => 'Контент',
            'views_count'  => 100,
            'published_at' => date('Y-m-d H:i:s'),
        ], [$catId]);

        // Increment views
        $this->postRepo->incrementViews($postId);

        $post = $this->postRepo->findById($postId);
        $this->assertSame(101, (int)$post['views_count']);
    }

    public function testSimilarPostsAlgorithm(): void
    {
        $cat1 = $this->categoryRepo->create(['title' => 'PHP', 'slug' => 'php']);
        $cat2 = $this->categoryRepo->create(['title' => 'MySQL', 'slug' => 'mysql']);
        $cat3 = $this->categoryRepo->create(['title' => 'CSS', 'slug' => 'css']);

        // Post A: has cat1 and cat2
        $postA = $this->postRepo->create([
            'title' => 'Post A', 'slug' => 'post-a', 'description' => 'desc', 'content' => 'text',
            'published_at' => '2026-01-01 10:00:00',
        ], [$cat1, $cat2]);

        // Post B: has cat1 and cat2 (shares 2 categories with Post A!)
        $postB = $this->postRepo->create([
            'title' => 'Post B', 'slug' => 'post-b', 'description' => 'desc', 'content' => 'text',
            'published_at' => '2026-01-02 10:00:00',
        ], [$cat1, $cat2]);

        // Post C: has only cat1 (shares 1 category with Post A)
        $postC = $this->postRepo->create([
            'title' => 'Post C', 'slug' => 'post-c', 'description' => 'desc', 'content' => 'text',
            'published_at' => '2026-01-03 10:00:00',
        ], [$cat1]);

        // Post D: has only cat3 (shares 0 categories with Post A)
        $postD = $this->postRepo->create([
            'title' => 'Post D', 'slug' => 'post-d', 'description' => 'desc', 'content' => 'text',
            'published_at' => '2026-01-04 10:00:00',
        ], [$cat3]);

        // Find similar posts for Post A
        $similar = $this->postRepo->getSimilarPosts($postA, [$cat1, $cat2], 3);

        $this->assertCount(2, $similar); // Should match Post B and Post C, but NOT Post D
        $this->assertSame('Post B', $similar[0]['title']); // Post B matches 2 categories, so ranked first!
        $this->assertSame('Post C', $similar[1]['title']); // Post C matches 1 category
    }

    public function testTopViewedPosts(): void
    {
        $catId = $this->categoryRepo->create(['title' => 'General', 'slug' => 'general']);

        $this->postRepo->create([
            'title' => 'Low Views', 'slug' => 'low', 'description' => 'd', 'content' => 'c',
            'views_count' => 10, 'published_at' => '2026-01-01 00:00:00',
        ], [$catId]);

        $this->postRepo->create([
            'title' => 'High Views', 'slug' => 'high', 'description' => 'd', 'content' => 'c',
            'views_count' => 500, 'published_at' => '2026-01-01 00:00:00',
        ], [$catId]);

        $this->postRepo->create([
            'title' => 'Medium Views', 'slug' => 'medium', 'description' => 'd', 'content' => 'c',
            'views_count' => 250, 'published_at' => '2026-01-01 00:00:00',
        ], [$catId]);

        $top = $this->postRepo->getTopViewed(2);

        $this->assertCount(2, $top);
        $this->assertSame('High Views', $top[0]['title']);
        $this->assertSame('Medium Views', $top[1]['title']);
    }
}

