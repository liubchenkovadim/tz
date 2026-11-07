<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;
use App\Enums\PostSort;
use PDO;

class PostRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Get latest N posts for a category (ordered by published_at DESC)
     */
    public function getLatestByCategory(int $categoryId, int $limit = 3): array
    {
        $sql = "
            SELECT p.*
            FROM posts p
            INNER JOIN post_categories pc ON p.id = pc.post_id
            WHERE pc.category_id = :category_id
            ORDER BY p.published_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        return $this->attachCategories($posts);
    }

    /**
     * Get paginated posts for a specific category with sorting
     */
    public function getPaginatedByCategory(int $categoryId, int $page, int $perPage, PostSort $sort): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $orderBy = $sort->getOrderBySql();

        $sql = "
            SELECT p.*
            FROM posts p
            INNER JOIN post_categories pc ON p.id = pc.post_id
            WHERE pc.category_id = :category_id
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        return $this->attachCategories($posts);
    }

    /**
     * Count total posts in a category
     */
    public function countByCategory(int $categoryId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM post_categories WHERE category_id = :category_id');
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Find post by slug along with all its categories
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch();

        if (!$post) {
            return null;
        }

        $categories = $this->getCategoriesForPost((int)$post['id']);
        $post['categories'] = $categories;

        return $post;
    }

    /**
     * Find post by ID along with all its categories
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch();

        if (!$post) {
            return null;
        }

        $categories = $this->getCategoriesForPost((int)$post['id']);
        $post['categories'] = $categories;

        return $post;
    }

    /**
     * Atomically increment post view count
     */
    public function incrementViews(int $postId): void
    {
        $stmt = $this->db->prepare('UPDATE posts SET views_count = views_count + 1 WHERE id = :id');
        $stmt->bindValue(':id', $postId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Find up to $limit similar posts based on shared categories
     *
     * @param int $currentPostId Current post ID to exclude
     * @param array<int> $categoryIds IDs of categories to find intersections with
     * @param int $limit Number of similar posts to return
     */
    public function getSimilarPosts(int $currentPostId, array $categoryIds, int $limit = 3): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        $sql = "
            SELECT p.*, COUNT(pc.category_id) AS matched_categories
            FROM posts p
            INNER JOIN post_categories pc ON p.id = pc.post_id
            WHERE pc.category_id IN ({$placeholders})
              AND p.id != ?
            GROUP BY p.id
            ORDER BY matched_categories DESC, p.published_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);

        $paramIndex = 1;
        foreach ($categoryIds as $catId) {
            $stmt->bindValue($paramIndex++, (int)$catId, PDO::PARAM_INT);
        }
        $stmt->bindValue($paramIndex++, $currentPostId, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);

        $stmt->execute();
        $posts = $stmt->fetchAll();

        return $this->attachCategories($posts);
    }

    /**
     * Get top N most viewed posts (for Admin Dashboard)
     */
    public function getTopViewed(int $limit = 10): array
    {
        $sql = "
            SELECT p.*
            FROM posts p
            ORDER BY p.views_count DESC, p.published_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        return $this->attachCategories($posts);
    }

    public function getTotalPostsCount(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    public function getTotalViewsCount(): int
    {
        return (int)$this->db->query('SELECT COALESCE(SUM(views_count), 0) FROM posts')->fetchColumn();
    }

    /**
     * Get paginated posts for Admin post list
     */
    public function getAdminPaginated(int $page, int $perPage = 10): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "
            SELECT p.*
            FROM posts p
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        return $this->attachCategories($posts);
    }

    /**
     * Create post with categories
     */
    public function create(array $data, array $categoryIds): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                INSERT INTO posts (title, slug, description, content, image_url, views_count, published_at)
                VALUES (:title, :slug, :description, :content, :image_url, :views_count, :published_at)
            ');

            $stmt->execute([
                'title'        => $data['title'],
                'slug'         => $data['slug'],
                'description'  => $data['description'],
                'content'      => $data['content'],
                'image_url'    => $data['image_url'] ?? null,
                'views_count'  => (int)($data['views_count'] ?? 0),
                'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
            ]);

            $postId = (int)$this->db->lastInsertId();

            if (!empty($categoryIds)) {
                $catStmt = $this->db->prepare('
                    INSERT INTO post_categories (post_id, category_id)
                    VALUES (:post_id, :category_id)
                ');
                foreach ($categoryIds as $catId) {
                    $catStmt->execute([
                        'post_id'     => $postId,
                        'category_id' => (int)$catId,
                    ]);
                }
            }

            $this->db->commit();
            $this->clearCache();

            return $postId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update post and sync categories
     */
    public function update(int $id, array $data, array $categoryIds): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('
                UPDATE posts
                SET title = :title,
                    slug = :slug,
                    description = :description,
                    content = :content,
                    image_url = :image_url,
                    views_count = :views_count,
                    published_at = :published_at
                WHERE id = :id
            ');

            $stmt->execute([
                'id'           => $id,
                'title'        => $data['title'],
                'slug'         => $data['slug'],
                'description'  => $data['description'],
                'content'      => $data['content'],
                'image_url'    => $data['image_url'] ?? null,
                'views_count'  => (int)($data['views_count'] ?? 0),
                'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
            ]);

            // Sync categories: delete old associations and re-insert
            $delStmt = $this->db->prepare('DELETE FROM post_categories WHERE post_id = :post_id');
            $delStmt->execute(['post_id' => $id]);

            if (!empty($categoryIds)) {
                $catStmt = $this->db->prepare('
                    INSERT INTO post_categories (post_id, category_id)
                    VALUES (:post_id, :category_id)
                ');
                foreach ($categoryIds as $catId) {
                    $catStmt->execute([
                        'post_id'     => $id,
                        'category_id' => (int)$catId,
                    ]);
                }
            }

            $this->db->commit();
            $this->clearCache();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = :id');
        $result = $stmt->execute(['id' => $id]);

        $this->clearCache();
        return $result;
    }

    /**
     * Get all categories belonging to a single post
     */
    public function getCategoriesForPost(int $postId): array
    {
        $sql = "
            SELECT c.id, c.slug, c.title
            FROM categories c
            INNER JOIN post_categories pc ON c.id = pc.category_id
            WHERE pc.post_id = :post_id
            ORDER BY c.title ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function attachCategories(array $posts): array
    {
        if (empty($posts)) {
            return [];
        }

        $postIds = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));

        $sql = "
            SELECT pc.post_id, c.id, c.slug, c.title
            FROM categories c
            INNER JOIN post_categories pc ON c.id = pc.category_id
            WHERE pc.post_id IN ({$placeholders})
            ORDER BY c.title ASC
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($postIds as $index => $id) {
            $stmt->bindValue($index + 1, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $relations = $stmt->fetchAll();

        $categoriesByPostId = [];
        foreach ($relations as $rel) {
            $categoriesByPostId[$rel['post_id']][] = [
                'id'    => $rel['id'],
                'slug'  => $rel['slug'],
                'title' => $rel['title'],
            ];
        }

        foreach ($posts as &$post) {
            $post['categories'] = $categoriesByPostId[$post['id']] ?? [];
        }

        return $posts;
    }

    private function clearCache(): void
    {
        Cache::deletePattern('home:*');
        Cache::delete('categories:all');
    }
}
