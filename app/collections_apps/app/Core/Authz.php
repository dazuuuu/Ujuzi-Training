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

    public static function learnerOrganisationIds(array $actor): array
    {
        $ids = [];
        try {
            $ids = OrganisationMembership::selectedOrganisationIds((int) $actor['id']);
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
        return self::isOrganisationAdmin($actor) || self::canCreateCourses($actor) || self::isStudent($actor);
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
        return in_array((int) $course['organisation_id'], self::learnerOrganisationIds($actor), true);
    }

    public static function canEditCourse(array $actor, array $course): bool
    {
        return (int) ($course['trainer_user_id'] ?? 0) === (int) $actor['id'] && self::canCreateCourses($actor);
    }
}
