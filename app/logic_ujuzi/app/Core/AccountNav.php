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

    /** The full catalogue of reorderable items for one portal, id => [icon, label, href]. */
    public static function catalogue(string $portal): array
    {
        return match ($portal) {
            self::PORTAL_STUDENT => [
                'courses' => ['📚', 'Courses', '/account/courses'],
                'certificate' => ['🎖️', 'Certificate', '/account/certificate'],
                'wallet' => ['💰', 'Wallet', '/account/wallet'],
                'course_organisations' => ['🏢', 'Organisation providing courses', '/account/course-organisations'],
                'attachment_providers' => ['🏭', 'Organisation providing attachment', '/account/attachment-providers'],
            ],
            self::PORTAL_ORGANISATION_ADMIN => [
                'courses' => ['📚', 'Courses', '/account/courses'],
                'trainer_requests' => ['📩', 'Requests', '/account/trainer-requests'],
                'wallet' => ['💰', 'Finances', '/account/wallet'],
                'organisation' => ['🏢', 'Organisation', '/account/organisation'],
                'people' => ['👥', 'People', '/account/people'],
                'categories' => ['📂', 'Categories', '/account/categories'],
                'branches' => ['📍', 'Branches', '/account/branches'],
            ],
            self::PORTAL_ATTACHMENT_TRAINER => [
                'organisation' => ['🏢', 'Organisation', '/account/organisation'],
                'people' => ['👥', 'People', '/account/people'],
                'branches' => ['📍', 'Branches', '/account/branches'],
            ],
            self::PORTAL_COURSE_BRANCH_ADMIN => [
                'course_branch_admin' => ['📩', 'Branch Requests', '/account/course-branch-admin'],
                'categories' => ['📂', 'Categories', '/account/categories'],
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
