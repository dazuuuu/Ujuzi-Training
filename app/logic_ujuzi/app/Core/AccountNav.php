<?php

namespace App\Core;

use App\Models\StoreSetting;

/**
 * The editable middle section of each portal's side nav (Dashboard at the
 * top, Profile and Logout at the bottom stay fixed), and Super Admin's own
 * menu. Super Admin -> Navigation lets you drag items into any order and
 * rename them per portal; the order is stored as a StoreSetting JSON array
 * of item ids (nav_order_<portal>) and the names as a JSON object of
 * id => label (nav_labels_<portal>). The public website menu is edited on
 * the same page but stored with the page builder's site settings.
 */
class AccountNav
{
    public const PORTAL_STUDENT = 'student';
    public const PORTAL_ORGANISATION_ADMIN = 'organisation_admin';
    public const PORTAL_ATTACHMENT_TRAINER = 'attachment_trainer';
    public const PORTAL_COURSE_BRANCH_ADMIN = 'course_branch_admin';
    public const PORTAL_TRAINER = 'trainer';
    public const PORTAL_BRANCH_ADMIN = 'branch_admin';
    public const PORTAL_ADMIN = 'admin';

    public static function portals(): array
    {
        return [
            self::PORTAL_ADMIN => 'Super Admin menu (your own)',
            self::PORTAL_STUDENT => 'Students Portal',
            self::PORTAL_TRAINER => 'Tutors Portal',
            self::PORTAL_ORGANISATION_ADMIN => 'Organisation (providing courses) Portal',
            self::PORTAL_COURSE_BRANCH_ADMIN => 'Branch Admin (Courses) Portal',
            self::PORTAL_ATTACHMENT_TRAINER => 'Organisation (providing attachment) Portal',
            self::PORTAL_BRANCH_ADMIN => 'Branch Admin (Attachment) Portal',
        ];
    }

    /** The full catalogue of reorderable items for one portal, id => [icon name (see icon()), label, href]. */
    public static function catalogue(string $portal): array
    {
        return match ($portal) {
            self::PORTAL_STUDENT => [
                'courses' => ['book', 'My courses', '/account/courses'],
                'certificate' => ['award', 'My Certificates', '/account/certificate'],
                'wallet' => ['wallet', 'My wallet', '/account/wallet'],
                'payments' => ['file', 'Payment history', '/account/payments'],
                'course_organisations' => ['building', 'Organisation providing courses', '/account/course-organisations'],
                'attachment_providers' => ['briefcase', 'Organisation providing attachment', '/account/attachment-providers'],
            ],
            self::PORTAL_ORGANISATION_ADMIN => [
                'courses' => ['book', 'Courses', '/account/courses'],
                'certificates' => ['award', 'Certificates', '/account/certificates'],
                'assessments' => ['file', 'Assessments', '/account/assessments'],
                'reports' => ['chart', 'Reports', '/account/reports'],
                'trainer_requests' => ['inbox', 'Requests', '/account/trainer-requests'],
                'attachment_partners' => ['briefcase', 'Attachment partners', '/account/attachment-partners'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
                'wallet' => ['wallet', 'Finances', '/account/wallet'],
                'organisation' => ['building', 'Organisation', '/account/organisation'],
                'people' => ['users', 'People', '/account/people'],
                'categories' => ['folder', 'Categories', '/account/categories'],
                'branches' => ['map-pin', 'Branches', '/account/branches'],
            ],
            self::PORTAL_ATTACHMENT_TRAINER => [
                'attachment_audience' => ['target', 'Where you appear', '/account/attachment-audience'],
                'assessment_template' => ['file', 'Assessment form', '/account/assessment-template'],
                'reports' => ['chart', 'Reports', '/account/reports'],
                'attachment_payments' => ['file', 'Attachment payments', '/account/attachment-payments'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
                'organisation' => ['building', 'Organisation', '/account/organisation'],
                'people' => ['users', 'People', '/account/people'],
                'branches' => ['map-pin', 'Branches', '/account/branches'],
            ],
            self::PORTAL_COURSE_BRANCH_ADMIN => [
                'course_branch_admin' => ['inbox', 'Branch Requests', '/account/course-branch-admin'],
                'branch_students' => ['users', 'Students', '/account/branch-students'],
                'courses' => ['book', 'Branch courses', '/account/courses'],
                'assessments' => ['file', 'Assessments', '/account/assessments'],
                'wallet' => ['wallet', 'Finances', '/account/wallet'],
                'reports' => ['chart', 'Reports', '/account/reports'],
                'certificates' => ['award', 'Certificates', '/account/certificates'],
                'categories' => ['folder', 'Categories', '/account/categories'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
            ],
            self::PORTAL_TRAINER => [
                'courses' => ['book', 'Courses', '/account/courses'],
                'wallet' => ['wallet', 'Earnings', '/account/wallet'],
            ],
            self::PORTAL_BRANCH_ADMIN => [
                'branch_admin' => ['graduation', 'Attachees', '/account/branch-admin'],
                'reports' => ['chart', 'Reports', '/account/reports'],
                'attachment_payments' => ['file', 'Attachment payments', '/account/attachment-payments'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
            ],
            self::PORTAL_ADMIN => [
                'dashboard' => ['dashboard', 'Dashboard', '/admin'],
                'guide' => ['help', 'System guide & help', '/admin/guide'],
                'roles' => ['settings', 'Roles', '/admin/roles'],
                'organisations' => ['building', 'Organisations', '/admin/organisations'],
                'share' => ['arrow-right', 'Share registration', '/admin/share-registration'],
                'registered-users' => ['users', 'Registered users', '/admin/registered-users'],
                'students' => ['graduation', 'Students', '/admin/registered-users?role_slug=student'],
                'attachment-providers' => ['briefcase', 'Attachment providers', '/admin/registered-users?role_slug=attachment_trainer'],
                'course-organisations' => ['building', 'Organisations providing courses', '/admin/registered-users?role_slug=organisation_admin'],
                'course-approvals' => ['check', 'Course approvals', '/admin/course-approvals'],
                'delete-requests' => ['x', 'Delete requests', '/admin/delete-requests'],
                'change-requests' => ['key', 'Detail change requests', '/admin/change-requests'],
                'forms' => ['file', 'Forms', '/admin/forms'],
                'updates' => ['download', 'Updates', '/admin/updates'],
                'attachments' => ['briefcase', 'Attachments', '/admin/attachments'],
                'finance' => ['wallet', 'Finance', '/admin/finance'],
                'reports' => ['chart', 'Reports', '/admin/reports'],
                'student-lookup' => ['search', 'Student lookup', '/admin/student-lookup'],
                'documents' => ['file', 'Documents', '/admin/documents'],
                'certificates' => ['award', 'Certificates', '/admin/certificates'],
                'data-cleanup' => ['x', 'Data Cleanup', '/admin/data-cleanup'],
                'navigation' => ['menu', 'Navigation', '/admin/navigation'],
                'pages' => ['globe', 'Public pages', '/admin/pages'],
                'homepage' => ['dashboard', 'Logos & featured courses', '/admin/homepage'],
                'settings' => ['settings', 'Settings', '/admin/settings'],
                'admins' => ['user', 'Admins', '/admin/admins'],
                'error-logs' => ['file', 'Error logs', '/admin/error-logs'],
            ],
            default => [],
        };
    }

    /** Super Admin's renames for one portal: [item id => label]. */
    public static function labelsFor(string $portal): array
    {
        $stored = json_decode((string) StoreSetting::get('nav_labels_' . $portal), true);
        if (!is_array($stored)) {
            return [];
        }
        $catalogue = self::catalogue($portal);
        $out = [];
        foreach ($stored as $id => $label) {
            $label = trim((string) $label);
            if (isset($catalogue[$id]) && $label !== '') {
                $out[$id] = mb_substr($label, 0, 60);
            }
        }
        return $out;
    }

    /** Saves renames; a blank name or one equal to the default goes back to the default. */
    public static function saveLabels(string $portal, array $labels): void
    {
        $catalogue = self::catalogue($portal);
        $out = [];
        foreach ($labels as $id => $label) {
            $label = trim(preg_replace('/\s+/', ' ', (string) $label));
            if (isset($catalogue[$id]) && $label !== '' && $label !== $catalogue[$id][1]) {
                $out[$id] = mb_substr($label, 0, 60);
            }
        }
        StoreSetting::set('nav_labels_' . $portal, $out ? json_encode($out, JSON_UNESCAPED_UNICODE) : null);
    }

    /** One item's name as shown (renamed, else the default) — for page titles and tab bars. */
    public static function label(string $portal, string $id): ?string
    {
        return self::labelsFor($portal)[$id] ?? (self::catalogue($portal)[$id][1] ?? null);
    }

    /** Item ids in the order Super Admin chose (falls back to the catalogue's natural order). */
    public static function orderFor(string $portal): array
    {
        $catalogue = self::catalogue($portal);
        $ids = array_keys($catalogue);
        try {
            $stored = StoreSetting::get('nav_order_' . $portal);
        } catch (\Throwable $e) {
            $stored = null;
        }
        if (!$stored) {
            return $ids;
        }
        $custom = json_decode($stored, true);
        if (!is_array($custom)) {
            return $ids;
        }
        $custom = array_values(array_intersect($custom, $ids));
        $missing = array_values(array_diff($ids, $custom));
        return array_merge($custom, $missing);
    }

    public static function saveOrder(string $portal, array $orderedIds): void
    {
        $valid = array_keys(self::catalogue($portal));
        $orderedIds = array_values(array_intersect($orderedIds, $valid));
        StoreSetting::set('nav_order_' . $portal, json_encode($orderedIds));
    }

    /** Ordered [id => [icon, label, href]] for one portal with Super Admin's names, ready to render. */
    public static function orderedItems(string $portal): array
    {
        $catalogue = self::catalogue($portal);
        try {
            $labels = self::labelsFor($portal);
        } catch (\Throwable $e) {
            $labels = [];
        }
        $ordered = [];
        foreach (self::orderFor($portal) as $id) {
            if (isset($catalogue[$id])) {
                $ordered[$id] = $catalogue[$id];
                if (isset($labels[$id])) {
                    $ordered[$id][1] = $labels[$id];
                }
            }
        }
        return $ordered;
    }

    /** The default (built-in) name of each item: [id => label]. */
    public static function defaultLabels(string $portal): array
    {
        return array_map(static fn(array $item): string => $item[1], self::catalogue($portal));
    }
}
