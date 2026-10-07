<?php

namespace App\Services;

use App\Models\StoreSetting;

class RecommendationLetterService
{
    public static function templatePath(): ?string
    {
        $relative = StoreSetting::get('recommendation_letter_template');
        return $relative !== null && $relative !== '' ? $relative : null;
    }

    public static function templateAbsolute(): ?string
    {
        $relative = self::templatePath();
        if (!$relative) {
            return null;
        }
        $full = dirname(__DIR__, 4) . '/public/' . ltrim($relative, '/');
        return is_file($full) ? $full : null;
    }

    public static function isPdf(?string $path = null): bool
    {
        $path = $path ?: (string) self::templatePath();
        return strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public static function isImage(?string $path = null): bool
    {
        $path = $path ?: (string) self::templatePath();
        return in_array(strtolower((string) pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public static function payload(array $student, array $application): array
    {
        return [
            'learner' => userFullName($student),
            'registration_number' => (string) (($student['registration_number'] ?? '') ?: (\App\Models\User::assignRegistrationNumber((int) ($student['id'] ?? 0)) ?? '')),
            'course' => (string) ($application['course_title'] ?? '') ?: self::coursesInCategory((int) $student['id'], (int) ($application['category_id'] ?? 0)),
            'provider' => trim((string) ($application['first_name'] ?? '') . ' ' . (string) ($application['last_name'] ?? '')),
            'organisation' => (string) ($application['organisation_name'] ?? ''),
            'branch' => (string) ($application['branch_title'] ?? ''),
            'issued' => date('j F Y', strtotime((string) ($application['recommended_at'] ?? 'now'))),
            'template' => self::templatePath(),
            'is_pdf' => self::isPdf(),
            'is_image' => self::isImage(),
        ];
    }

    /** The courses a student took in the category their attachment was for. */
    private static function coursesInCategory(int $studentId, int $categoryId): string
    {
        if ($categoryId < 1) {
            return '';
        }
        $stmt = \App\Core\Database::connection()->prepare(
            'SELECT DISTINCT c.title FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN course_categories cc ON cc.course_id = c.id
             WHERE e.user_id = ? AND cc.category_id = ? ORDER BY c.title'
        );
        $stmt->execute([$studentId, $categoryId]);
        return implode(', ', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
