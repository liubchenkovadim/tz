<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;
use PDO;

class CategoryRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Get all categories that contain at least one post
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategoriesWithPosts(): array
    {
        $sql = "
            SELECT c.id, c.slug, c.title, c.description, c.created_at, COUNT(pc.post_id) AS posts_count
            FROM categories c
            INNER JOIN post_categories pc ON c.id = pc.category_id
            GROUP BY c.id, c.slug, c.title, c.description, c.created_at
            HAVING posts_count > 0
            ORDER BY c.title ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find category by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Get all categories (cached)
     */
    public function getAll(): array
    {
        return Cache::remember('categories:all', 300, function () {
            $stmt = $this->db->query('SELECT * FROM categories ORDER BY title ASC');
            return $stmt->fetchAll();
        });
    }

    /**
     * Get all categories with total posts count (for admin list)
     */
    public function getAllWithStats(): array
    {
        $sql = "
            SELECT c.*, COUNT(pc.post_id) AS posts_count
            FROM categories c
            LEFT JOIN post_categories pc ON c.id = pc.category_id
            GROUP BY c.id
            ORDER BY c.id DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getTotalCategoriesCount(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO categories (title, slug, description)
            VALUES (:title, :slug, :description)
        ');

        $stmt->execute([
            'title'       => $data['title'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        $this->clearCache();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE categories
            SET title = :title, slug = :slug, description = :description
            WHERE id = :id
        ');

        $result = $stmt->execute([
            'id'          => $id,
            'title'       => $data['title'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        $this->clearCache();
        return $result;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $result = $stmt->execute(['id' => $id]);

        $this->clearCache();
        return $result;
    }

    private function clearCache(): void
    {
        Cache::delete('categories:all');
        Cache::deletePattern('home:*');
    }
}
