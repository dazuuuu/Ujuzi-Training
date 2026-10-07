<?php

namespace App\Core;

use App\Models\StoreSetting;

/**
 * The reorderable middle section of each portal's side nav (Home/Dashboard
 * at the top and Logout at the bottom stay fixed). Super Admin -> Navigation
 * lets you drag these into whatever order you want per portal; the chosen
 * order is stored as a StoreSetting JSON array of item ids.
 */
class AccountNav
{
    public const PORTAL_STUDENT = 'student';
    public const PORTAL_ORGANISATION_ADMIN = 'organisation_admin';
    public const PORTAL_ATTACHMENT_TRAINER = 'attachment_trainer';
    public const PORTAL_COURSE_BRANCH_ADMIN = 'course_branch_admin';

    public static function portals(): array
    {
        return [
            self::PORTAL_STUDENT => 'Students Portal',
            self::PORTAL_ORGANISATION_ADMIN => 'Organisation (providing courses) Portal',
            self::PORTAL_ATTACHMENT_TRAINER => 'Attachment Provider Portal',
            self::PORTAL_COURSE_BRANCH_ADMIN => 'Branch Admin (Courses) Portal',
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
                'reports' => ['chart', 'Reports', '/account/reports'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
                'organisation' => ['building', 'Organisation', '/account/organisation'],
                'people' => ['users', 'People', '/account/people'],
                'branches' => ['map-pin', 'Branches', '/account/branches'],
            ],
            self::PORTAL_COURSE_BRANCH_ADMIN => [
                'course_branch_admin' => ['inbox', 'Branch Requests', '/account/course-branch-admin'],
                'branch_students' => ['users', 'Students', '/account/branch-students'],
                'assessments' => ['file', 'Assessments', '/account/assessments'],
                'wallet' => ['wallet', 'Finances', '/account/wallet'],
                'reports' => ['chart', 'Reports', '/account/reports'],
                'certificates' => ['award', 'Certificates', '/account/certificates'],
                'categories' => ['folder', 'Categories', '/account/categories'],
                'verify_certificate' => ['search', 'Student lookup', '/account/student-lookup'],
            ],
            default => [],
        };
    }

    /** Item ids in the order Super Admin chose (falls back to the catalogue's natural order). */
    public static function orderFor(string $portal): array
    {
        $catalogue = self::catalogue($portal);
        $ids = array_keys($catalogue);
        $stored = StoreSetting::get('nav_order_' . $portal);
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

    /** Ordered [id => [icon, label, href]] for one portal, ready to render. */
    public static function orderedItems(string $portal): array
    {
        $catalogue = self::catalogue($portal);
        $ordered = [];
        foreach (self::orderFor($portal) as $id) {
            if (isset($catalogue[$id])) {
                $ordered[$id] = $catalogue[$id];
            }
        }
        return $ordered;
    }
}
