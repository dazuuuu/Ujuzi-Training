<?php

namespace App\Models;

use App\Core\Database;

class OrganisationMembership
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public static function trainerRoleSlugs(): array
    {
        return ['trainer', 'attachment_trainer'];
    }

    public static function isTrainerRole(string $slug): bool
    {
        return in_array($slug, self::trainerRoleSlugs(), true);
    }

    public static function isStudentRole(string $slug): bool
    {
        return $slug === 'student';
    }

    public static function selectedOrganisationIds(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT organisation_id FROM organisation_memberships
             WHERE user_id = ? AND status IN ('approved', 'pending')"
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.email, u.phone, u.first_name, u.last_name, u.organisation_id AS user_organisation_id,
                    r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN organisations o ON o.id = m.organisation_id
             WHERE m.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, o.name AS organisation_name
             FROM organisation_memberships m
             INNER JOIN organisations o ON o.id = m.organisation_id
             WHERE m.user_id = ?
             ORDER BY m.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function pendingTrainersForOrganisation(int $organisationId): array
    {
        $placeholders = implode(',', array_fill(0, count(self::trainerRoleSlugs()), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT m.*, u.email, u.phone, u.first_name, u.last_name,
                    r.slug AS role_slug, r.name AS role_name
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE m.organisation_id = ?
               AND m.status = 'pending'
               AND r.slug IN ($placeholders)
             ORDER BY m.created_at DESC"
        );
        $stmt->execute(array_merge([$organisationId], self::trainerRoleSlugs()));
        return $stmt->fetchAll();
    }

    public static function approvedOrganisationIds(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT organisation_id FROM organisation_memberships WHERE user_id = ? AND status = 'approved'"
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function isApproved(int $userId, int $organisationId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM organisation_memberships
             WHERE user_id = ? AND organisation_id = ? AND status = 'approved'
             LIMIT 1"
        );
        $stmt->execute([$userId, $organisationId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function ensureApproved(int $userId, int $organisationId, ?int $reviewerUserId = null): void
    {
        if ($userId < 1 || $organisationId < 1) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, status FROM organisation_memberships WHERE user_id = ? AND organisation_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $organisationId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === self::STATUS_APPROVED) {
                return;
            }
            $pdo->prepare(
                "UPDATE organisation_memberships
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = ?
                 WHERE id = ?"
            )->execute([$reviewerUserId, (int) $existing['id']]);
            return;
        }

        $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status, reviewed_by_user_id, reviewed_at)
             VALUES (?, ?, 'approved', ?, NOW())"
        )->execute([$userId, $organisationId, $reviewerUserId]);
    }

    public static function approve(int $id, int $organisationId, int $reviewerUserId): bool
    {
        $row = self::find($id);
        if (!$row || (int) $row['organisation_id'] !== $organisationId || $row['status'] !== self::STATUS_PENDING) {
            return false;
        }

        Database::connection()->prepare(
            "UPDATE organisation_memberships
             SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = ?
             WHERE id = ?"
        )->execute([$reviewerUserId, $id]);

        $user = User::find((int) $row['user_id']);
        if ($user && empty($user['organisation_id'])) {
            User::setOrganisationId((int) $user['id'], $organisationId);
        }

        return true;
    }

    public static function reject(int $id, int $organisationId, int $reviewerUserId): bool
    {
        $row = self::find($id);
        if (!$row || (int) $row['organisation_id'] !== $organisationId || $row['status'] !== self::STATUS_PENDING) {
            return false;
        }

        Database::connection()->prepare(
            "UPDATE organisation_memberships
             SET status = 'rejected', reviewed_at = NOW(), reviewed_by_user_id = ?
             WHERE id = ?"
        )->execute([$reviewerUserId, $id]);

        return true;
    }

    public static function syncFromProfileAnswers(int $userId, string $roleSlug, array $fields, array $answers): void
    {
        $orgIds = [];
        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') !== 'organisation') {
                continue;
            }
            $raw = $answers[$field['field_key']] ?? [];
            if (!is_array($raw)) {
                $raw = $raw !== '' && $raw !== null ? [$raw] : [];
            }
            foreach ($raw as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $orgIds[$id] = $id;
                }
            }
        }
        $orgIds = array_values($orgIds);

        if (self::isTrainerRole($roleSlug)) {
            self::syncSelections($userId, $orgIds);
            return;
        }

        if (self::isStudentRole($roleSlug)) {
            self::syncStudentSelections($userId, $orgIds);
        }
    }

    private static function syncStudentSelections(int $userId, array $selectedOrgIds): void
    {
        $selected = [];
        foreach ($selectedOrgIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }

        $existing = self::forUser($userId);
        $pdo = Database::connection();

        foreach ($existing as $row) {
            $orgId = (int) $row['organisation_id'];
            if (!empty($selected[$orgId])) {
                if ($row['status'] !== self::STATUS_APPROVED) {
                    $pdo->prepare(
                        "UPDATE organisation_memberships
                         SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = NULL
                         WHERE id = ?"
                    )->execute([(int) $row['id']]);
                }
                unset($selected[$orgId]);
                continue;
            }
            $pdo->prepare('DELETE FROM organisation_memberships WHERE id = ?')->execute([(int) $row['id']]);
        }

        $insert = $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status, reviewed_at)
             VALUES (?, ?, 'approved', NOW())"
        );
        foreach (array_keys($selected) as $orgId) {
            $insert->execute([$userId, $orgId]);
        }

        $primary = (int) ($selectedOrgIds[0] ?? 0);
        User::assignOrganisation($userId, $primary > 0 ? $primary : null);
    }

    private static function syncSelections(int $userId, array $selectedOrgIds): void
    {
        $selected = [];
        foreach ($selectedOrgIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }

        $existing = self::forUser($userId);
        $pdo = Database::connection();

        foreach ($existing as $row) {
            $orgId = (int) $row['organisation_id'];
            if (!empty($selected[$orgId])) {
                if ($row['status'] === self::STATUS_REJECTED) {
                    $pdo->prepare(
                        "UPDATE organisation_memberships
                         SET status = 'pending', reviewed_at = NULL, reviewed_by_user_id = NULL
                         WHERE id = ?"
                    )->execute([(int) $row['id']]);
                }
                unset($selected[$orgId]);
                continue;
            }

            if ($row['status'] === self::STATUS_PENDING) {
                $pdo->prepare('DELETE FROM organisation_memberships WHERE id = ?')->execute([(int) $row['id']]);
            }
        }

        $insert = $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status)
             VALUES (?, ?, 'pending')"
        );
        foreach (array_keys($selected) as $orgId) {
            $insert->execute([$userId, $orgId]);
        }
    }
}
