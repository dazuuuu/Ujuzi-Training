<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * Course certificates. Every organisation providing courses can upload its
 * own certificate design; a student's certificate always uses the design of
 * the organisation that runs the course, falling back to the platform-wide
 * design Super Admin uploaded, and to the built-in design when neither exists.
 */
class CertificateService
{
    private static function key(?int $organisationId): string
    {
        return 'certificate_template' . ($organisationId ? '_org_' . $organisationId : '');
    }

    /** The organisation's own design only (null when it has none). */
    public static function ownTemplatePath(?int $organisationId): ?string
    {
        $relative = StoreSetting::get(self::key($organisationId));
        return $relative !== null && $relative !== '' ? $relative : null;
    }

    public const MODE_GLOBAL = 'global';
    public const MODE_ORGANISATION = 'organisation';

    /**
     * Which certificate an organisation issues: the global Ujuzi Training
     * certificate, or its own organisational certificate. Organisations that
     * uploaded a design before this choice existed keep using it.
     */
    public static function mode(?int $organisationId): string
    {
        if (!$organisationId) {
            return self::MODE_GLOBAL;
        }
        $mode = (string) StoreSetting::get('certificate_mode_org_' . $organisationId, '');
        if ($mode === self::MODE_GLOBAL || $mode === self::MODE_ORGANISATION) {
            return $mode;
        }
        return self::ownTemplatePath($organisationId) ? self::MODE_ORGANISATION : self::MODE_GLOBAL;
    }

    public static function setMode(int $organisationId, string $mode): void
    {
        StoreSetting::set('certificate_mode_org_' . $organisationId, $mode === self::MODE_ORGANISATION ? self::MODE_ORGANISATION : self::MODE_GLOBAL);
    }

    /** The organisation whose design and positions a certificate uses (null = the global certificate). */
    public static function designOwner(?int $organisationId): ?int
    {
        return $organisationId && self::mode($organisationId) === self::MODE_ORGANISATION && self::ownTemplatePath($organisationId)
            ? $organisationId
            : null;
    }

    /** The design a certificate from this organisation uses: its own when it chose that, else the global one. */
    public static function templatePath(?int $organisationId = null): ?string
    {
        $owner = self::designOwner($organisationId);
        return ($owner ? self::ownTemplatePath($owner) : null) ?? self::ownTemplatePath(null);
    }

    public static function setTemplate(?int $organisationId, ?string $path): void
    {
        $current = self::ownTemplatePath($organisationId);
        if ($current && $current !== $path) {
            UploadService::delete($current);
        }
        StoreSetting::set(self::key($organisationId), $path);
    }

    /** Stores an uploaded design (PDF or image) and returns its path. */
    public static function storeUpload(array $file): string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return UploadService::storeDocument($file, 'certificates');
        }
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new UploadException('Upload a PDF or an image (JPG, PNG, WEBP, GIF) of the certificate design.');
        }
        return UploadService::store($file, 'certificates');
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

    /** One course's certificate for one student, on that course organisation's design. */
    public static function payload(array $user, array $completedCourses): array
    {
        $skills = \App\Models\Course::skillNames($completedCourses);
        $organisationId = (int) ($completedCourses[0]['organisation_id'] ?? 0) ?: null;
        $template = self::templatePath($organisationId);
        $owner = self::designOwner($organisationId);
        return [
            'learner' => userFullName($user),
            'registration_number' => (string) ($user['registration_number'] ?? (\App\Models\User::assignRegistrationNumber((int) ($user['id'] ?? 0)) ?? '')),
            'organisation' => (string) ($user['organisation_name'] ?? ''),
            'skills' => $skills,
            'course' => implode(', ', $skills),
            'course_organisation' => implode(', ', array_values(array_unique(array_filter(array_map(
                static fn(array $c): string => (string) ($c['organisation_name'] ?? ''),
                $completedCourses
            ))))),
            'issued' => date('j F Y'),
            'template' => $template,
            'is_pdf' => $template ? self::isPdf($template) : false,
            'is_image' => $template ? self::isImage($template) : false,
            'layout' => DocumentLayout::get('certificate', $owner),
        ];
    }
}
