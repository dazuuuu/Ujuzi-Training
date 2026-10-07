<?php

namespace App\Services;

use App\Core\Database;

/**
 * A course enrollment lasts RETAKE_MONTHS. Within that time a student may
 * reset their progress and take the course again without paying. Once it
 * passes, the course resets: progress is cleared, earlier payments stop
 * counting towards it, and the student must enrol and pay again. A course
 * already completed keeps its certificate (see course_completions).
 */
class CourseRetake
{
    public const RETAKE_MONTHS = 6;

    /** When the student's current run of the course ends, as a timestamp, or null if not enrolled. */
    public static function endsAt(int $userId, int $courseId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT enrolled_at FROM course_enrollments WHERE user_id = ? AND course_id = ? LIMIT 1');
        $stmt->execute([$userId, $courseId]);
        $enrolledAt = $stmt->fetchColumn();
        if (!$enrolledAt) {
            return null;
        }
        return strtotime((string) $enrolledAt . ' +' . self::RETAKE_MONTHS . ' months') ?: null;
    }

    /** True while the student is enrolled and still inside the free-retake window. */
    public static function canRetakeFree(int $userId, int $courseId): bool
    {
        $ends = self::endsAt($userId, $courseId);
        return $ends !== null && $ends > time();
    }

    /** Starts the course over inside the window: progress and the final exam are cleared, payments stay. */
    public static function restart(int $userId, int $courseId): bool
    {
        if (!self::canRetakeFree($userId, $courseId)) {
            return false;
        }
        self::recordCompletionIfDone($userId, $courseId);
        self::clearProgress($userId, $courseId);
        return true;
    }

    /**
     * Resets every one of the student's courses whose run has ended, so they
     * enrol and pay again. Called when the student opens their courses.
     */
    public static function expireDue(int $userId): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT course_id FROM course_enrollments
                 WHERE user_id = ? AND enrolled_at IS NOT NULL AND enrolled_at < NOW() - INTERVAL ' . self::RETAKE_MONTHS . ' MONTH'
            );
            $stmt->execute([$userId]);
            $due = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            return; // before the retake migration
        }
        foreach ($due as $courseId) {
            self::expire($userId, $courseId);
        }
    }

    private static function expire(int $userId, int $courseId): void
    {
        $pdo = Database::connection();
        self::recordCompletionIfDone($userId, $courseId);
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE wallet_transactions SET archived_at = NOW()
                 WHERE user_id = ? AND course_id = ? AND type = 'course_payment' AND archived_at IS NULL"
            )->execute([$userId, $courseId]);
            $pdo->prepare('DELETE FROM course_module_access WHERE user_id = ? AND course_id = ?')->execute([$userId, $courseId]);
            self::clearProgress($userId, $courseId);
            $pdo->prepare('DELETE FROM course_enrollments WHERE user_id = ? AND course_id = ?')->execute([$userId, $courseId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function clearProgress(int $userId, int $courseId): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM course_module_progress WHERE user_id = ? AND course_id = ?')->execute([$userId, $courseId]);
        $pdo->prepare('DELETE FROM course_final_exam_progress WHERE user_id = ? AND course_id = ?')->execute([$userId, $courseId]);
    }

    /** Keeps a finished course on record (and so on the certificate) before its progress is cleared. */
    public static function recordCompletionIfDone(int $userId, int $courseId): void
    {
        if (\App\Models\Course::isCompletedByUser($courseId, $userId)) {
            self::recordCompletion($userId, $courseId);
        }
    }

    public static function recordCompletion(int $userId, int $courseId, ?int $score = null): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT IGNORE INTO course_completions (user_id, course_id, score, completed_at) VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([$userId, $courseId, $score]);
            if ($stmt->rowCount() === 1) {
                $course = \App\Models\Course::find($courseId);
                \App\Services\Notifier::toUser($userId, 'Congratulations — you completed ' . ($course['title'] ?? 'your course'), 'Course completed', 'You completed ' . ($course['title'] ?? 'your course') . '. If the course gives a certificate, it is ready in My Certificates.', 'My certificates', '/account/certificate?course=' . $courseId);
            }
        } catch (\Throwable $e) {
            // before the retake migration
        }
    }

    public static function hasCompletion(int $userId, int $courseId): bool
    {
        try {
            $stmt = Database::connection()->prepare('SELECT 1 FROM course_completions WHERE user_id = ? AND course_id = ? LIMIT 1');
            $stmt->execute([$userId, $courseId]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
