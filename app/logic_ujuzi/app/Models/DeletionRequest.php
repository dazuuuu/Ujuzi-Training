<?php

namespace App\Models;

use App\Core\Database;

/**
 * Organisations don't delete things themselves: pressing Delete sends a
 * request to Super Admin, who deletes it on approval. A thing can only be
 * requested once while waiting, and a request can't be withdrawn.
 */
class DeletionRequest
{
    /** What can be asked for: type => [label, deletes it]. */
    public static function types(): array
    {
        return [
            'branch' => ['Branch', static fn(int $id) => OrganisationBranch::delete($id)],
            'course' => ['Course', static fn(int $id) => Course::delete($id)],
            'module' => ['Course module', static fn(int $id) => CourseModule::delete($id)],
            'category' => ['Category', static fn(int $id) => OrganisationCategory::delete($id)],
        ];
    }

    public static function pendingFor(string $type, int $id): ?array
    {
        try {
            $stmt = Database::connection()->prepare("SELECT * FROM deletion_requests WHERE entity_type = ? AND entity_id = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$type, $id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null; // before the update that adds deletion requests
        }
    }

    /**
     * Sends the request. Returns null when sent, else why not (already
     * requested, or the update hasn't been run yet).
     */
    public static function request(string $type, int $id, string $label, ?string $organisationName, int $userId, string $reason): ?string
    {
        if (!isset(self::types()[$type])) {
            return 'That cannot be deleted.';
        }
        if (self::pendingFor($type, $id)) {
            return 'You already asked Super Admin to delete this. Requests can be sent only once and are waiting for Super Admin.';
        }
        try {
            Database::connection()->prepare(
                'INSERT INTO deletion_requests (entity_type, entity_id, label, organisation_name, requested_by_user_id, reason) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$type, $id, mb_substr($label, 0, 255), $organisationName, $userId, mb_substr(trim($reason), 0, 500) ?: null]);
        } catch (\Throwable $e) {
            return 'Deleting needs Super Admin to run the latest update first.';
        }
        return null;
    }

    public static function pending(): array
    {
        try {
            return Database::connection()->query(
                "SELECT r.*, u.first_name, u.last_name, u.email, u.phone, ro.name AS role_name
                 FROM deletion_requests r
                 INNER JOIN users u ON u.id = r.requested_by_user_id
                 INNER JOIN roles ro ON ro.id = u.role_id
                 WHERE r.status = 'pending' ORDER BY r.created_at ASC"
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function recent(int $limit = 30): array
    {
        try {
            return Database::connection()->query(
                "SELECT r.*, u.first_name, u.last_name, u.email FROM deletion_requests r
                 INNER JOIN users u ON u.id = r.requested_by_user_id
                 WHERE r.status <> 'pending' ORDER BY r.decided_at DESC LIMIT " . (int) $limit
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM deletion_requests WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Super Admin's answer: approving deletes the thing straight away. */
    public static function decide(int $id, bool $approve, int $adminId): ?array
    {
        $request = self::find($id);
        if (!$request || $request['status'] !== 'pending') {
            return null;
        }
        if ($approve) {
            (self::types()[$request['entity_type']][1] ?? static fn() => null)((int) $request['entity_id']);
        }
        Database::connection()->prepare('UPDATE deletion_requests SET status = ?, decided_by_admin_id = ?, decided_at = NOW() WHERE id = ?')
            ->execute([$approve ? 'approved' : 'rejected', $adminId, $id]);
        return $request;
    }
}
