<?php

namespace App\Core;

use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;

class Authz
{
    public static function canManageUsers(array $actor): bool
    {
        return !empty($actor['can_manage_users']) && !empty($actor['has_admin_features']);
    }

    public static function manageableRoles(array $actor): array
    {
        $slugs = $actor['managed_role_slugs'] ?? [];
        if (!$slugs) {
            return [];
        }
        return Role::findBySlugs($slugs);
    }

    public static function canManageRole(array $actor, string $roleSlug): bool
    {
        return in_array($roleSlug, $actor['managed_role_slugs'] ?? [], true);
    }

    public static function canAccessUser(array $actor, array $target): bool
    {
        if ((int) $actor['id'] === (int) $target['id']) {
            return true;
        }
        if (!self::canManageUsers($actor)) {
            return false;
        }
        $orgId = (int) ($actor['organisation_id'] ?? 0);
        if ($orgId < 1) {
            return false;
        }
        $sameOrg = (int) ($target['organisation_id'] ?? 0) === $orgId;
        $approvedMember = false;
        try {
            $approvedMember = OrganisationMembership::isApproved((int) $target['id'], $orgId);
        } catch (\Throwable $e) {
            $approvedMember = false;
        }
        if (!$sameOrg && !$approvedMember) {
            return false;
        }
        return self::canManageRole($actor, $target['role_slug'] ?? '');
    }

    public static function scopedUsers(array $actor): array
    {
        if (!self::canManageUsers($actor)) {
            return [];
        }
        $orgId = (int) ($actor['organisation_id'] ?? 0);
        if ($orgId < 1) {
            return [];
        }
        $allowed = $actor['managed_role_slugs'] ?? [];
        try {
            $users = User::allForOrganisation($orgId);
        } catch (\Throwable $e) {
            $users = User::all($orgId);
        }
        return array_values(array_filter(
            $users,
            fn(array $user): bool => in_array($user['role_slug'], $allowed, true)
        ));
    }

    public static function isOrganisationAdmin(array $actor): bool
    {
        return ($actor['role_slug'] ?? '') === 'organisation_admin' && !empty($actor['organisation_id']);
    }

    public static function approvedOrganisationIds(array $actor): array
    {
        $ids = [];
        try {
            $ids = OrganisationMembership::approvedOrganisationIds((int) $actor['id']);
        } catch (\Throwable $e) {
            $ids = [];
        }
        if (!$ids && !empty($actor['organisation_id']) && OrganisationMembership::isTrainerRole((string) ($actor['role_slug'] ?? ''))) {
            $ids[] = (int) $actor['organisation_id'];
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public static function isStudent(array $actor): bool
    {
        return ($actor['role_slug'] ?? '') === 'student';
    }

    /** Categories a student is approved for, across every organisation they're approved under. */
    public static function approvedCategoryIds(array $actor): array
    {
        $ids = [];
        try {
            foreach (OrganisationMembership::forUser((int) $actor['id']) as $membership) {
                if (($membership['status'] ?? '') === OrganisationMembership::STATUS_APPROVED) {
                    foreach ($membership['category_ids'] ?? [] as $categoryId) {
                        $ids[] = (int) $categoryId;
                    }
                }
            }
        } catch (\Throwable $e) {
            $ids = [];
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public static function learnerOrganisationIds(array $actor): array
    {
        // Only approved memberships count — a pending join request must not
        // unlock that organisation's strict-visibility courses early.
        $ids = [];
        try {
            $ids = OrganisationMembership::approvedOrganisationIds((int) $actor['id']);
        } catch (\Throwable $e) {
            $ids = [];
        }
        if (!$ids && !empty($actor['organisation_id'])) {
            $ids[] = (int) $actor['organisation_id'];
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public static function canCreateCourses(array $actor): bool
    {
        return OrganisationMembership::isTrainerRole((string) ($actor['role_slug'] ?? ''))
            && self::approvedOrganisationIds($actor) !== [];
    }

    public static function canViewCourses(array $actor): bool
    {
        return self::isOrganisationAdmin($actor)
            || ($actor['role_slug'] ?? '') === 'trainer'
            || self::isStudent($actor);
    }

    public static function canManageBranches(array $actor): bool
    {
        return self::isOrganisationAdmin($actor) || ($actor['role_slug'] ?? '') === 'attachment_trainer';
    }

    public static function canAccessCourse(array $actor, array $course): bool
    {
        if (self::isOrganisationAdmin($actor) && (int) $course['organisation_id'] === (int) $actor['organisation_id']) {
            return true;
        }
        if ((int) ($course['trainer_user_id'] ?? 0) === (int) $actor['id'] && self::canCreateCourses($actor)) {
            return true;
        }
        if (!self::isStudent($actor) || empty($course['is_published'])) {
            return false;
        }
        if (($course['visibility'] ?? 'strict') === 'global') {
            return true;
        }
        if (!in_array((int) $course['organisation_id'], self::learnerOrganisationIds($actor), true)) {
            return false;
        }
        // Matches if ANY of the course's categories is open, or one the student is approved for.
        $courseCategoryIds = \App\Models\Course::categoryIdsFor((int) $course['id']);
        if (!$courseCategoryIds) {
            $courseCategoryIds = array_filter([(int) ($course['category_id'] ?? 0)]);
        }
        if (!$courseCategoryIds) {
            return false;
        }
        if (\App\Models\OrganisationCategory::anyOpen($courseCategoryIds)) {
            return true;
        }
        $approved = self::approvedCategoryIds($actor);
        return (bool) array_intersect($courseCategoryIds, $approved);
    }

    public static function canEditCourse(array $actor, array $course): bool
    {
        return (int) ($course['trainer_user_id'] ?? 0) === (int) $actor['id'] && self::canCreateCourses($actor);
    }
}
