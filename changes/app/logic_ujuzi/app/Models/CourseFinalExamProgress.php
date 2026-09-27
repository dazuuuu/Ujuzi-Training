<?php

namespace App\Models;

use App\Core\Database;

class CourseFinalExamProgress
{
    public static function findForUserCourse(int $userId, int $courseId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM course_final_exam_progress WHERE user_id = ? AND course_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $courseId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function saveAttempt(array $fields): void
    {
        $pdo = Database::connection();
        $existing = self::findForUserCourse((int) $fields['user_id'], (int) $fields['course_id']);
        $passed = !empty($fields['passed']);
        $answers = isset($fields['answers']) ? json_encode($fields['answers']) : null;

        if ($existing) {
            $keepPassed = !empty($existing['passed']) || $passed;
            $pdo->prepare(
                'UPDATE course_final_exam_progress
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
            'INSERT INTO course_final_exam_progress (user_id, course_id, score, passed, answers, completed_at)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['user_id'],
            (int) $fields['course_id'],
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
