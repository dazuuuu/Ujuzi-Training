<?php

namespace App\Services;

use App\Models\StoreSetting;

class CertificateService
{
    public static function templatePath(): ?string
    {
        $relative = StoreSetting::get('certificate_template');
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

    public static function payload(array $user, array $completedCourses): array
    {
        $skills = \App\Models\Course::skillNames($completedCourses);
        return [
            'learner' => userDisplayName($user),
            'organisation' => (string) ($user['organisation_name'] ?? ''),
            'skills' => $skills,
            'issued' => date('j F Y'),
            'template' => self::templatePath(),
            'is_pdf' => self::isPdf(),
            'is_image' => self::isImage(),
        ];
    }
}
