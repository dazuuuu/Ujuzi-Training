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

    public static function adminUsersByOrganisation(): array
    {
        $stmt = Database::connection()->query(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.organisation_id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'organisation_admin'
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['organisation_id']][] = $row;
        }
        return $out;
    }

    public static function adminUsers(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT u.id, u.email, u.first_name, u.last_name, u.other_names, u.is_active
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'organisation_admin' AND u.organisation_id = ?
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }

    public static function active(): array
    {
        return Database::connection()
            ->query('SELECT * FROM organisations WHERE is_active = 1 ORDER BY name ASC')
            ->fetchAll();
    }

    /**
     * Active organisations that actually run courses (have at least one
     * organisation_admin) AND chose to appear on the student dashboard.
     * An organisation admin can still onboard students directly (People page)
     * even while hidden here.
     */
    public static function providingCourses(): array
    {
        return Database::connection()->query(
            "SELECT DISTINCT o.*
             FROM organisations o
             INNER JOIN users u ON u.organisation_id = o.id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE o.is_active = 1 AND o.visible_to_students = 1 AND r.slug = 'organisation_admin' AND u.is_active = 1
             ORDER BY o.name ASC"
        )->fetchAll();
    }

    public static function searchActive(string $query = '', int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $query = trim($query);
        if ($query === '') {
            $stmt = Database::connection()->prepare(
                'SELECT id, name FROM organisations WHERE is_active = 1 ORDER BY name ASC LIMIT ' . $limit
            );
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, name FROM organisations
             WHERE is_active = 1 AND name LIKE ?
             ORDER BY name ASC
             LIMIT ' . $limit
        );
        $stmt->execute(['%' . $query . '%']);
        return $stmt->fetchAll();
    }

    public static function activeIds(): array
    {
        return array_map(fn(array $row): int => (int) $row['id'], self::active());
    }

    public static function validActiveIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id FROM organisations WHERE is_active = 1 AND id IN ($placeholders)"
        );
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
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
        $pdo->prepare('INSERT INTO organisations (name, slug, description, is_active, visible_to_students) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $fields['name'],
                $fields['slug'],
                $fields['description'] ?? null,
                !empty($fields['is_active']) ? 1 : 0,
                array_key_exists('visible_to_students', $fields) ? (!empty($fields['visible_to_students']) ? 1 : 0) : 1,
            ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE organisations SET name = ?, slug = ?, description = ?, is_active = ?, visible_to_students = ? WHERE id = ?'
        )->execute([
            $fields['name'],
            $fields['slug'],
            $fields['description'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            array_key_exists('visible_to_students', $fields) ? (!empty($fields['visible_to_students']) ? 1 : 0) : 1,
            $id,
        ]);
    }

    /** Cascades to its categories, courses (+modules/enrollments/attachment applications), branches, memberships, and invites. */
    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM organisations WHERE id = ?')->execute([$id]);
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
