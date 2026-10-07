<?php

namespace App\Core;

/**
 * What a Super Admin may open. Owners have everything and are the only ones
 * who manage admins; other admins get just the sections an owner ticked.
 * Every admin page is gated here by its URL, so a hidden menu item can't be
 * reached by typing its address either.
 */
class AdminAccess
{
    /** Sections an owner can grant, key => label. */
    public const SECTIONS = [
        'organisations' => 'Organisations & share registration',
        'users' => 'Users (registered users, students, providers)',
        'roles' => 'Roles',
        'forms' => 'Forms',
        'attachments' => 'Attachments',
        'finance' => 'Finance',
        'reports' => 'Reports',
        'documents' => 'Documents & certificates',
        'homepage' => 'Public pages, homepage content & featured courses',
        'navigation' => 'Portal navigation',
        'settings' => 'Settings',
        'updates' => 'Updates',
        'data-cleanup' => 'Data cleanup',
    ];

    /** First URL segment after /admin => section. Anything not listed is owner-only. */
    private const PATH_SECTIONS = [
        '' => 'dashboard',
        'organisations' => 'organisations',
        'share-registration' => 'organisations',
        'users' => 'users',
        'registered-users' => 'users',
        'roles' => 'roles',
        'forms' => 'forms',
        'attachments' => 'attachments',
        'finance' => 'finance',
        'reports' => 'reports',
        'student-lookup' => 'users',
        'documents' => 'documents',
        'certificate' => 'documents',
        'homepage' => 'homepage',
        'pages' => 'homepage',
        'theme' => 'homepage',
        'course-approvals' => 'organisations',
        'branch-deletions' => 'organisations',
        'navigation' => 'navigation',
        'settings' => 'settings',
        'updates' => 'updates',
        'data-cleanup' => 'data-cleanup',
        'admins' => 'admins',
    ];

    /** Admin menu item id => section. */
    private const NAV_SECTIONS = [
        'dashboard' => 'dashboard', 'roles' => 'roles', 'organisations' => 'organisations', 'share' => 'organisations',
        'registered-users' => 'users', 'students' => 'users', 'attachment-providers' => 'users', 'course-organisations' => 'users',
        'forms' => 'forms', 'updates' => 'updates', 'attachments' => 'attachments', 'finance' => 'finance',
        'reports' => 'reports', 'student-lookup' => 'users',
        'documents' => 'documents', 'data-cleanup' => 'data-cleanup', 'navigation' => 'navigation', 'settings' => 'settings',
        'homepage' => 'homepage', 'pages' => 'homepage', 'course-approvals' => 'organisations', 'admins' => 'admins',
    ];

    public static function sectionForPath(string $path): string
    {
        $segment = explode('/', trim(preg_replace('#^/admin#', '', $path), '/'))[0] ?? '';
        return self::PATH_SECTIONS[$segment] ?? 'admins';
    }

    public static function allows(array $admin, string $section): bool
    {
        if ($section === 'dashboard' || !empty($admin['is_owner'])) {
            return true;
        }
        if ($section === 'admins') {
            return false;
        }
        return in_array($section, $admin['permissions'] ?? [], true);
    }

    public static function allowsNav(array $admin, string $navId): bool
    {
        return self::allows($admin, self::NAV_SECTIONS[$navId] ?? 'admins');
    }

    /** Keeps only known section keys. */
    public static function clean(array $keys): array
    {
        return array_values(array_intersect(array_keys(self::SECTIONS), array_map('strval', $keys)));
    }
}
