<?php

namespace App\Core;

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
        if ((int) ($actor['organisation_id'] ?? 0) !== (int) ($target['organisation_id'] ?? 0)) {
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
        return array_values(array_filter(
            User::all($orgId),
            fn(array $user): bool => in_array($user['role_slug'], $allowed, true)
        ));
    }
}
