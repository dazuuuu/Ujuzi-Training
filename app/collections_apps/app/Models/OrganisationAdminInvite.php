<?php

namespace App\Models;

use App\Core\Database;

class OrganisationAdminInvite
{
    public const LIFETIME_SECONDS = 300;

    public static function latestForOrganisation(int $organisationId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM organisation_admin_invites WHERE organisation_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$organisationId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function latestByOrganisation(): array
    {
        $rows = Database::connection()->query(
            'SELECT i.*
             FROM organisation_admin_invites i
             INNER JOIN (
                SELECT organisation_id, MAX(id) AS max_id
                FROM organisation_admin_invites
                GROUP BY organisation_id
             ) latest ON latest.max_id = i.id'
        )->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $invite = self::hydrate($row);
            $out[(int) $invite['organisation_id']] = $invite;
        }
        return $out;
    }

    public static function mint(int $organisationId, int $adminId): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE organisation_admin_invites
                 SET expires_at = LEAST(expires_at, NOW())
                 WHERE organisation_id = ? AND used_at IS NULL AND expires_at > NOW()'
            )->execute([$organisationId]);

            $plain = bin2hex(random_bytes(32));
            $pdo->prepare(
                'INSERT INTO organisation_admin_invites (organisation_id, token_hash, expires_at, created_by_admin_id)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), ?)'
            )->execute([$organisationId, hash('sha256', $plain), $adminId]);

            $id = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT expires_at FROM organisation_admin_invites WHERE id = ?');
            $stmt->execute([$id]);
            $expires = (string) $stmt->fetchColumn();
            $pdo->commit();

            return [
                'id' => $id,
                'organisation_id' => $organisationId,
                'token' => $plain,
                'url' => url('/register/organisation-admin/' . $plain),
                'expires_at' => $expires,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findValidByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = Database::connection()->prepare(
            'SELECT i.*, o.name AS organisation_name, o.is_active AS organisation_is_active
             FROM organisation_admin_invites i
             INNER JOIN organisations o ON o.id = i.organisation_id
             WHERE i.token_hash = ?'
        );
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return self::hydrate($row);
    }

    public static function markUsed(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE organisation_admin_invites
             SET used_at = NOW(), used_by_user_id = ?
             WHERE id = ? AND used_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$userId, $id]);
        return $stmt->rowCount() === 1;
    }

    public static function markEmailed(int $id, string $email): void
    {
        Database::connection()->prepare(
            'UPDATE organisation_admin_invites SET emailed_to = ? WHERE id = ?'
        )->execute([$email, $id]);
    }

    public static function statusMessage(array $invite): string
    {
        if (!empty($invite['used_at'])) {
            return 'This link was already used. Generate a new one to invite another organisation admin.';
        }
        if (!empty($invite['is_expired'])) {
            return 'This link expired after 5 minutes. Generate a new secure URL.';
        }
        return 'A registration link is currently active. Generate a new one to replace it.';
    }

    public static function hydrate(array $row): array
    {
        $row['is_used'] = !empty($row['used_at']);
        $row['is_expired'] = empty($row['used_at']) && strtotime((string) $row['expires_at']) <= time();
        $row['is_active'] = empty($row['used_at']) && !$row['is_expired'];
        $row['organisation_is_active'] = isset($row['organisation_is_active'])
            ? (int) $row['organisation_is_active'] === 1
            : true;
        return $row;
    }
}
