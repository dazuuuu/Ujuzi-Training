<?php

namespace App\Models;

use App\Core\Database;

class CourseModuleProgress
{
    public static function forUserCourse(int $userId, int $courseId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM course_module_progress WHERE user_id = ? AND course_id = ?'
        );
        $stmt->execute([$userId, $courseId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['module_id']] = self::hydrate($row);
        }
        return $out;
    }

    public static function findForUserModule(int $userId, int $moduleId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM course_module_progress WHERE user_id = ? AND module_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $moduleId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function saveAttempt(array $fields): void
    {
        $pdo = Database::connection();
        $existing = self::findForUserModule((int) $fields['user_id'], (int) $fields['module_id']);
        $passed = !empty($fields['passed']);
        $answers = isset($fields['answers']) ? json_encode($fields['answers']) : null;

        if ($existing) {
            $keepPassed = !empty($existing['passed']) || $passed;
            $pdo->prepare(
                'UPDATE course_module_progress
                 SET score = ?, passed = ?, answers = ?, completed_at = ?
                 WHERE id = ?'
            )->execute([
                (int) $fields['score'],
                $keepPassed ? 1 : 0,
                $answers,
                $keepPassed ? ($existing['completed_at'] ?: date('Y-m-d H:i:s')) : null,
                (int) $existing['id'],
            ]);
            return;
        }

        $pdo->prepare(
            'INSERT INTO course_module_progress (user_id, course_id, module_id, score, passed, answers, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['user_id'],
            (int) $fields['course_id'],
            (int) $fields['module_id'],
            (int) $fields['score'],
            $passed ? 1 : 0,
            $answers,
            $passed ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public static function hydrate(array $row): array
    {
        $answers = $row['answers'] ?? null;
        if (is_string($answers) && $answers !== '') {
            $row['answers'] = json_decode($answers, true) ?: [];
        } elseif (!is_array($answers)) {
            $row['answers'] = [];
        }
        $row['passed'] = !empty($row['passed']);
        $row['score'] = (int) ($row['score'] ?? 0);
        return $row;
    }
}
