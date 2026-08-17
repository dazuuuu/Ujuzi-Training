<?php

namespace App\Models;

use App\Core\Database;

class OrganisationCategory
{
    public static function forOrganisation(int $organisationId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM organisation_categories WHERE organisation_id = ?';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, o.name AS organisation_name
             FROM organisation_categories c
             INNER JOIN organisations o ON o.id = c.organisation_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function groupedForUser(?array $user): array
    {
        if (!$user) {
            return [];
        }
        $orgIds = [];
        try {
            foreach (OrganisationMembership::forUser((int) $user['id']) as $row) {
                if (($row['status'] ?? '') === OrganisationMembership::STATUS_APPROVED) {
                    $orgIds[] = (int) $row['organisation_id'];
                }
            }
        } catch (\Throwable $e) {
            $orgIds = [];
        }
        if (!$orgIds && !empty($user['organisation_id']) && OrganisationMembership::isTrainerRole((string) ($user['role_slug'] ?? ''))) {
            $orgIds[] = (int) $user['organisation_id'];
        }
        $orgIds = array_values(array_unique(array_filter($orgIds)));
        if (!$orgIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT c.*, o.name AS organisation_name
             FROM organisation_categories c
             INNER JOIN organisations o ON o.id = c.organisation_id
             WHERE c.organisation_id IN ($placeholders) AND c.is_active = 1
             ORDER BY o.name ASC, c.sort_order ASC, c.name ASC"
        );
        $stmt->execute($orgIds);
        $groups = [];
        foreach ($stmt->fetchAll() as $row) {
            $orgName = (string) $row['organisation_name'];
            $groups[$orgName][] = $row;
        }
        return $groups;
    }

    public static function namesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, name FROM organisation_categories WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $names[(int) $row['id']] = (string) $row['name'];
        }
        return $names;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO organisation_categories (organisation_id, name, slug, description, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['organisation_id'],
            $fields['name'],
            $fields['slug'],
            $fields['description'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            (int) ($fields['sort_order'] ?? 0),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE organisation_categories SET name = ?, slug = ?, description = ?, is_active = ?, sort_order = ? WHERE id = ?'
        )->execute([
            $fields['name'],
            $fields['slug'],
            $fields['description'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            (int) ($fields['sort_order'] ?? 0),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM organisation_categories WHERE id = ?')->execute([$id]);
    }

    public static function uniqueSlug(int $organisationId, string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'category';
        $slug = $base;
        $i = 2;
        while (self::slugExists($organisationId, $slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private static function slugExists(int $organisationId, string $slug, ?int $ignoreId): bool
    {
        if ($ignoreId) {
            $stmt = Database::connection()->prepare(
                'SELECT id FROM organisation_categories WHERE organisation_id = ? AND slug = ? AND id <> ?'
            );
            $stmt->execute([$organisationId, $slug, $ignoreId]);
        } else {
            $stmt = Database::connection()->prepare(
                'SELECT id FROM organisation_categories WHERE organisation_id = ? AND slug = ?'
            );
            $stmt->execute([$organisationId, $slug]);
        }
        return (bool) $stmt->fetchColumn();
    }
}
