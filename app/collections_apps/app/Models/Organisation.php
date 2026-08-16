<?php

namespace App\Models;

use App\Core\Database;

class Organisation
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM organisations ORDER BY name ASC')
            ->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM organisations')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organisations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO organisations (name, slug, description, is_active) VALUES (?, ?, ?, ?)')
            ->execute([
                $fields['name'],
                $fields['slug'],
                $fields['description'] ?? null,
                !empty($fields['is_active']) ? 1 : 0,
            ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE organisations SET name = ?, slug = ?, description = ?, is_active = ? WHERE id = ?'
        )->execute([
            $fields['name'],
            $fields['slug'],
            $fields['description'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'organisation';
        $slug = $base;
        $i = 2;
        while (self::slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private static function slugExists(string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId) {
            $stmt = Database::connection()->prepare('SELECT id FROM organisations WHERE slug = ? AND id <> ?');
            $stmt->execute([$slug, $ignoreId]);
        } else {
            $stmt = Database::connection()->prepare('SELECT id FROM organisations WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        return (bool) $stmt->fetchColumn();
    }
}
