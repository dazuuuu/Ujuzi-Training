<?php

namespace App\Models;

use App\Core\Database;

class OrganisationBranch
{
    public static function forOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM organisation_branches WHERE organisation_id = ? ORDER BY sort_order ASC, title ASC'
        );
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organisation_branches WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO organisation_branches (organisation_id, title, location, cover_image, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['organisation_id'],
            $fields['title'],
            $fields['location'],
            $fields['cover_image'] ?? null,
            (int) ($fields['sort_order'] ?? 0),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE organisation_branches SET title = ?, location = ?, cover_image = ?, sort_order = ? WHERE id = ?'
        )->execute([
            $fields['title'],
            $fields['location'],
            $fields['cover_image'] ?? null,
            (int) ($fields['sort_order'] ?? 0),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM organisation_branches WHERE id = ?')->execute([$id]);
    }
}
