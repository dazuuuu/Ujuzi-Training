<?php

namespace App\Models;

use App\Core\Database;

/**
 * Deleting a branch takes two yeses: the organisation head (who asks for it)
 * and Super Admin (who approves it, which deletes the branch).
 */
class BranchDeletionRequest
{
    public static function pendingForBranch(int $branchId): ?array
    {
        try {
            $stmt = Database::connection()->prepare("SELECT * FROM branch_deletion_requests WHERE branch_id = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$branchId]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null; // before the update adding the table
        }
    }

    /** The organisation head asks; their approval is recorded with the request. */
    public static function request(int $branchId, int $headUserId, string $reason): void
    {
        if (self::pendingForBranch($branchId)) {
            return;
        }
        Database::connection()->prepare(
            'INSERT INTO branch_deletion_requests (branch_id, requested_by_user_id, reason, organisation_approved_at, organisation_approved_by)
             VALUES (?, ?, ?, NOW(), ?)'
        )->execute([$branchId, $headUserId, mb_substr($reason, 0, 500) ?: null, $headUserId]);
    }

    public static function cancel(int $branchId): void
    {
        Database::connection()->prepare("DELETE FROM branch_deletion_requests WHERE branch_id = ? AND status = 'pending'")->execute([$branchId]);
    }

    public static function pending(): array
    {
        try {
            return Database::connection()->query(
                "SELECT r.*, b.title AS branch_title, b.location AS branch_location,
                        COALESCE(o.name, po.name) AS organisation_name,
                        u.first_name, u.last_name, u.email
                 FROM branch_deletion_requests r
                 INNER JOIN organisation_branches b ON b.id = r.branch_id
                 LEFT JOIN organisations o ON o.id = b.organisation_id
                 LEFT JOIN users owner ON owner.id = b.owner_user_id
                 LEFT JOIN organisations po ON po.id = owner.organisation_id
                 INNER JOIN users u ON u.id = r.requested_by_user_id
                 WHERE r.status = 'pending' ORDER BY r.created_at ASC"
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM branch_deletion_requests WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function decide(int $id, bool $approve, int $adminId): void
    {
        Database::connection()->prepare(
            'UPDATE branch_deletion_requests SET status = ?, admin_approved_at = NOW(), admin_approved_by = ? WHERE id = ?'
        )->execute([$approve ? 'approved' : 'rejected', $adminId, $id]);
    }
}
