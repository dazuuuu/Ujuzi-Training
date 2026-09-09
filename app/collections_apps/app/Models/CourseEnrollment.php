<?php

namespace App\Models;

use App\Core\Database;

class CourseEnrollment
{
    public static function enroll(int $userId, int $courseId): void
    {
        Database::connection()->prepare(
            'INSERT IGNORE INTO course_enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())'
        )->execute([$userId, $courseId]);
    }

    public static function isEnrolled(int $userId, int $courseId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM course_enrollments WHERE user_id = ? AND course_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $courseId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function idsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT course_id FROM course_enrollments WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
