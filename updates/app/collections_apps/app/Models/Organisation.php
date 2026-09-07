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

    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT * FROM organisations WHERE is_active = 1 ORDER BY name ASC')
            ->fetchAll();
    }

    public static function activeIds(): array
    {
        return array_map(fn(array $row): int => (int) $row['id'], self::active());
    }

    public static function namesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, name FROM organisations WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $names[(int) $row['id']] = (string) $row['name'];
        }
        return $names;
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
