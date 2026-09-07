<?php

namespace App\Models;

use App\Core\Database;

class Course
{
    public const VISIBILITY_STRICT = 'strict';
    public const VISIBILITY_GLOBAL = 'global';

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

    public static function studentsForTrainer(int $trainerUserId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT u.*, r.slug AS role_slug, r.name AS role_name,
                    o.name AS organisation_name, GROUP_CONCAT(DISTINCT c.title ORDER BY c.title SEPARATOR ', ') AS enrolled_courses
             FROM course_module_progress p
             INNER JOIN courses c ON c.id = p.course_id AND c.trainer_user_id = ?
             INNER JOIN users u ON u.id = p.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
             LEFT JOIN organisations o ON o.id = u.organisation_id
             GROUP BY u.id
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute([$trainerUserId]);
        return array_map([User::class, 'hydrate'], $stmt->fetchAll());
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

    public static function forLearner(array $organisationIds): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds))));
        $sql = 'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                       u.first_name, u.last_name, u.email
                FROM courses c
                INNER JOIN organisation_categories cat ON cat.id = c.category_id
                INNER JOIN organisations o ON o.id = c.organisation_id
                INNER JOIN users u ON u.id = c.trainer_user_id
                WHERE c.is_published = 1 AND (c.visibility = \'global\'';
        $params = [];
        if ($organisationIds) {
            $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
            $sql .= " OR (c.visibility = 'strict' AND c.organisation_id IN ($placeholders))";
            $params = $organisationIds;
        }
        $sql .= ') ORDER BY c.visibility DESC, c.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function completedByLearner(array $user): array
    {
        $courses = [];
        try {
            $courses = self::forLearner(\App\Core\Authz::learnerOrganisationIds($user));
        } catch (\Throwable $e) {
            return [];
        }
        $completed = [];
        foreach ($courses as $course) {
            if (self::isCompletedByUser((int) $course['id'], (int) $user['id'])) {
                $completed[] = $course;
            }
        }
        return $completed;
    }

    public static function isCompletedByUser(int $courseId, int $userId): bool
    {
        $modules = CourseModule::forCourse($courseId);
        if (!$modules) {
            return false;
        }
        try {
            $progress = CourseModuleProgress::forUserCourse($userId, $courseId);
        } catch (\Throwable $e) {
            $progress = [];
        }
        $state = CourseModule::withUnlockState($modules, $progress);
        foreach ($state as $module) {
            if (empty($module['is_unlocked']) || empty($module['is_passed'])) {
                return false;
            }
        }
        return true;
    }

    public static function skillNames(array $courses): array
    {
        $skills = [];
        foreach ($courses as $course) {
            $skill = trim((string) ($course['title'] ?? ''));
            $category = trim((string) ($course['category_name'] ?? ''));
            $label = $skill !== '' ? $skill : $category;
            if ($label === '') {
                continue;
            }
            if ($category !== '' && strcasecmp($skill, $category) !== 0) {
                $label = $skill . ' (' . $category . ')';
            }
            if (!in_array($label, $skills, true)) {
                $skills[] = $label;
            }
        }
        return $skills;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO courses (organisation_id, category_id, trainer_user_id, form_id, title, description, cover_image, materials, answers, is_published, visibility)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            self::normalizeVisibility($fields['visibility'] ?? null),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE courses
             SET organisation_id = ?, category_id = ?, title = ?, description = ?, cover_image = ?, materials = ?, answers = ?, is_published = ?, visibility = ?
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
            self::normalizeVisibility($fields['visibility'] ?? null),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
    }

    public static function normalizeVisibility(?string $value): string
    {
        return $value === self::VISIBILITY_GLOBAL ? self::VISIBILITY_GLOBAL : self::VISIBILITY_STRICT;
    }

    public static function isGlobal(array $course): bool
    {
        return ($course['visibility'] ?? self::VISIBILITY_STRICT) === self::VISIBILITY_GLOBAL;
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
        $row['visibility'] = self::normalizeVisibility($row['visibility'] ?? null);
        return $row;
    }
}
