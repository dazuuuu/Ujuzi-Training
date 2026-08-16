<?php

namespace App\Models;

use App\Core\Database;

class Role
{
    public static function all(): array
    {
        $rows = Database::connection()
            ->query('SELECT * FROM roles ORDER BY sort_order ASC, name ASC')
            ->fetchAll();
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM roles WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlugs(array $slugs): array
    {
        if (!$slugs) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = Database::connection()->prepare("SELECT * FROM roles WHERE slug IN ($placeholders) ORDER BY sort_order ASC");
        $stmt->execute(array_values($slugs));
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE roles SET name = ?, description = ?, has_admin_features = ?, is_under_organisation = ?, can_manage_users = ?, managed_role_slugs = ?, sort_order = ? WHERE id = ?'
        )->execute([
            $fields['name'],
            $fields['description'],
            !empty($fields['has_admin_features']) ? 1 : 0,
            !empty($fields['is_under_organisation']) ? 1 : 0,
            !empty($fields['can_manage_users']) ? 1 : 0,
            json_encode(array_values($fields['managed_role_slugs'] ?? [])),
            (int) ($fields['sort_order'] ?? 0),
            $id,
        ]);
    }

    public static function hydrate(array $row): array
    {
        $managed = $row['managed_role_slugs'] ?? '[]';
        if (is_string($managed)) {
            $managed = json_decode($managed, true) ?: [];
        }
        $row['managed_role_slugs'] = array_values(array_filter((array) $managed));
        $row['has_admin_features'] = (int) $row['has_admin_features'] === 1;
        $row['is_under_organisation'] = (int) $row['is_under_organisation'] === 1;
        $row['can_manage_users'] = (int) $row['can_manage_users'] === 1;
        return $row;
    }
}
