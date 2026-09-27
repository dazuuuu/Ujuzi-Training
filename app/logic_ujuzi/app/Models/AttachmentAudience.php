<?php

namespace App\Models;

use App\Core\Database;

/**
 * Where an attachment provider appears. A provider picks the organisations
 * providing courses and, within each, the categories whose students it takes
 * on — no approval by the organisation is involved. A student sees a provider
 * once they are enrolled in a course under one of those categories.
 */
class AttachmentAudience
{
    /** @return int[] the category ids the provider appears under */
    public static function categoryIdsFor(int $providerUserId): array
    {
        $stmt = Database::connection()->prepare('SELECT category_id FROM attachment_provider_audiences WHERE provider_user_id = ?');
        $stmt->execute([$providerUserId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Replaces the provider's audience. Only active categories of active,
     * student-visible organisations are kept; anything else is dropped.
     * Returns how many categories were saved.
     */
    public static function sync(int $providerUserId, array $categoryIds): int
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), static fn(int $id): bool => $id > 0)));
        $valid = [];
        if ($categoryIds) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $stmt = Database::connection()->prepare(
                "SELECT c.id, c.organisation_id
                 FROM organisation_categories c
                 INNER JOIN organisations o ON o.id = c.organisation_id
                 WHERE c.id IN ($placeholders) AND c.is_active = 1 AND o.is_active = 1 AND o.visible_to_students = 1"
            );
            $stmt->execute($categoryIds);
            $valid = $stmt->fetchAll();
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM attachment_provider_audiences WHERE provider_user_id = ?')->execute([$providerUserId]);
            $insert = $pdo->prepare('INSERT INTO attachment_provider_audiences (provider_user_id, organisation_id, category_id) VALUES (?, ?, ?)');
            foreach ($valid as $row) {
                $insert->execute([$providerUserId, (int) $row['organisation_id'], (int) $row['id']]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return count($valid);
    }

    /**
     * The course categories a student can request attachment under: the
     * categories of the courses they are enrolled in, keyed by id, each with
     * its organisation and the titles of those enrolled courses.
     */
    public static function studentCategories(int $studentUserId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT cat.id, cat.name, cat.organisation_id, o.name AS organisation_name, c.title AS course_title
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN course_categories cc ON cc.course_id = c.id
             INNER JOIN organisation_categories cat ON cat.id = cc.category_id
             INNER JOIN organisations o ON o.id = cat.organisation_id
             WHERE e.user_id = ? AND cat.is_active = 1 AND o.is_active = 1
             ORDER BY o.name ASC, cat.sort_order ASC, cat.name ASC, c.title ASC'
        );
        $stmt->execute([$studentUserId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (!isset($out[$id])) {
                $out[$id] = [
                    'id' => $id,
                    'name' => $row['name'],
                    'organisation_id' => (int) $row['organisation_id'],
                    'organisation_name' => $row['organisation_name'],
                    'courses' => [],
                ];
            }
            if (!in_array($row['course_title'], $out[$id]['courses'], true)) {
                $out[$id]['courses'][] = $row['course_title'];
            }
        }
        return $out;
    }

    /** @return array<int, int[]> provider user id => the given category ids it targets */
    public static function providersForCategories(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$categoryIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT provider_user_id, category_id FROM attachment_provider_audiences WHERE category_id IN ($placeholders)"
        );
        $stmt->execute($categoryIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['provider_user_id']][] = (int) $row['category_id'];
        }
        return $out;
    }
}
