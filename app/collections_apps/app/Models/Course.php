<?php

namespace App\Models;

use App\Core\Database;

class Course
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function forTrainer(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.trainer_user_id = ?
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.organisation_id = ?
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([$organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO courses (organisation_id, category_id, trainer_user_id, form_id, title, description, cover_image, materials, answers, is_published)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['organisation_id'],
            (int) $fields['category_id'],
            (int) $fields['trainer_user_id'],
            !empty($fields['form_id']) ? (int) $fields['form_id'] : null,
            $fields['title'],
            $fields['description'] ?? null,
            $fields['cover_image'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            isset($fields['answers']) ? json_encode($fields['answers']) : null,
            isset($fields['is_published']) && !$fields['is_published'] ? 0 : 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE courses
             SET organisation_id = ?, category_id = ?, title = ?, description = ?, cover_image = ?, materials = ?, answers = ?, is_published = ?
             WHERE id = ?'
        )->execute([
            (int) $fields['organisation_id'],
            (int) $fields['category_id'],
            $fields['title'],
            $fields['description'] ?? null,
            $fields['cover_image'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            isset($fields['answers']) ? json_encode($fields['answers']) : null,
            !empty($fields['is_published']) ? 1 : 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
    }

    public static function hydrate(array $row): array
    {
        foreach (['materials', 'answers'] as $key) {
            $value = $row[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $row[$key] = json_decode($value, true) ?: [];
            } elseif (!is_array($value)) {
                $row[$key] = [];
            }
        }
        $row['is_published'] = !empty($row['is_published']);
        return $row;
    }
}
