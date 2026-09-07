<?php

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
        return array_map([self::class, 'toArray'], $rows);
    }

    /** Raw rows + a live product_count — used by the admin listing. */
    public static function allWithCounts(): array
    {
        return Database::connection()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_key = c.category_key) AS product_count
             FROM categories c ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    }

    public static function findByKey(string $key): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE category_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? self::toArray($row) : null;
    }

    public static function findRawById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function keyExists(string $key): bool
    {
        $stmt = Database::connection()->prepare('SELECT id FROM categories WHERE category_key = ?');
        $stmt->execute([$key]);
        return (bool) $stmt->fetch();
    }

    public static function create(string $key, string $name, string $tagline, ?string $image, int $sortOrder): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO categories (category_key, name, tagline, image, sort_order) VALUES (?, ?, ?, ?, ?)')
            ->execute([$key, $name, $tagline, $image, $sortOrder]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, string $tagline, ?string $image, int $sortOrder): void
    {
        Database::connection()
            ->prepare('UPDATE categories SET name = ?, tagline = ?, image = ?, sort_order = ? WHERE id = ?')
            ->execute([$name, $tagline, $image, $sortOrder, $id]);
    }

    public static function clearImage(int $id): void
    {
        Database::connection()->prepare('UPDATE categories SET image = NULL WHERE id = ?')->execute([$id]);
    }

    private static function toArray(array $row): array
    {
        return [
            'id' => $row['category_key'],
            'dbId' => (int) $row['id'],
            'name' => $row['name'],
            'tagline' => $row['tagline'],
            'image' => $row['image'],
        ];
    }
}
