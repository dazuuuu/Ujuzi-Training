<?php

namespace App\Services;

use App\Core\Database;
use App\Models\OrganisationBranch;

/** What a branch admin is responsible for: their branches' organisation and its students. */
class BranchScope
{
    /**
     * For a course branch admin: [organisation id (0 if none), student ids
     * approved into their branches].
     */
    public static function courseBranch(int $userId): array
    {
        $branches = array_values(array_filter(OrganisationBranch::forBranchAdmin($userId), static fn(array $b): bool => !empty($b['organisation_id'])));
        if (!$branches) {
            return [0, []];
        }
        $orgId = (int) $branches[0]['organisation_id'];
        $branchIds = array_map(static fn(array $b): int => (int) $b['id'], $branches);
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT m.user_id FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
             WHERE m.status = 'approved' AND m.organisation_id = ? AND m.branch_id IN (" . implode(',', array_fill(0, count($branchIds), '?')) . ')'
        );
        $stmt->execute(array_merge([$orgId], $branchIds));
        return [$orgId, array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN))];
    }

    /** Branch ids a branch admin (attachment or courses) runs. */
    public static function branchIds(int $userId): array
    {
        return array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin($userId));
    }
}
