<?php

namespace App\Models;

use App\Core\Database;

class CourseEnrollment
{
    public static function enroll(int $userId, int $courseId, array $payment = []): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO course_enrollments (user_id, course_id, amount_ksh, currency, payment_provider, payment_status, payment_reference, enrolled_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                amount_ksh = VALUES(amount_ksh),
                currency = VALUES(currency),
                payment_provider = VALUES(payment_provider),
                payment_status = VALUES(payment_status),
                payment_reference = VALUES(payment_reference),
                enrolled_at = COALESCE(enrolled_at, NOW())'
        );
        $stmt->execute([
            $userId,
            $courseId,
            (float) ($payment['amount_ksh'] ?? 0),
            $payment['currency'] ?? 'KSH',
            $payment['payment_provider'] ?? 'manual',
            $payment['payment_status'] ?? 'paid',
            $payment['payment_reference'] ?? null,
        ]);
        if ($stmt->rowCount() === 1) { // a new enrolment, not a payment update
            $course = Course::find($courseId);
            if ($course) {
                \App\Services\Notifier::toUser($userId, 'You are enrolled: ' . $course['title'], 'You are enrolled', 'You are now enrolled for ' . $course['title'] . ' (' . ($course['organisation_name'] ?? '') . '). Start with the first module whenever you are ready.', 'Start learning', '/account/courses/' . $courseId);
            }
        }
    }

    public static function isEnrolled(int $userId, int $courseId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM course_enrollments WHERE user_id = ? AND course_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $courseId]);
        return (bool) $stmt->fetchColumn();
    }

    /** True once the student has enrolled for at least one course. */
    public static function hasAny(int $userId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM course_enrollments WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * True when nothing is still owed on the course: free courses, courses
     * enrolled while payments were closed, and fully-paid ones all count.
     */
    public static function isSettled(int $userId, int $courseId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT payment_status FROM course_enrollments WHERE user_id = ? AND course_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $courseId]);
        $status = $stmt->fetchColumn();
        return $status !== false && (string) $status === 'paid';
    }

    public static function idsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT course_id FROM course_enrollments WHERE user_id = ?');
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
