<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByIdentifier(string $type, string $value): ?array
    {
        $column = $type === 'email' ? 'email' : 'phone';
        $stmt = Database::connection()->prepare(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.$column = ?"
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function all(?int $organisationId = null, ?int $roleId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                       r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id
                WHERE 1=1';
        $params = [];
        if ($organisationId) {
            $sql .= ' AND u.organisation_id = ?';
            $params[] = $organisationId;
        }
        if ($roleId) {
            $sql .= ' AND u.role_id = ?';
            $params[] = $roleId;
        }
        $sql .= ' ORDER BY u.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function countByRole(): array
    {
        return Database::connection()->query(
            'SELECT r.slug, r.name, COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id, r.slug, r.name
             ORDER BY r.sort_order ASC'
        )->fetchAll();
    }

    public static function count(?int $organisationId = null): int
    {
        if ($organisationId) {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE organisation_id = ?');
            $stmt->execute([$organisationId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function recent(int $limit = 8, ?int $organisationId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id';
        $params = [];
        if ($organisationId) {
            $sql .= ' WHERE u.organisation_id = ?';
            $params[] = $organisationId;
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $email = trim((string) ($fields['email'] ?? ''));
        $phone = trim((string) ($fields['phone'] ?? ''));
        $passwordHash = null;
        if (!empty($fields['password'])) {
            $passwordHash = password_hash((string) $fields['password'], PASSWORD_DEFAULT);
        } elseif (!empty($fields['password_hash'])) {
            $passwordHash = (string) $fields['password_hash'];
        }
        $pdo->prepare(
            'INSERT INTO users (role_id, organisation_id, email, password_hash, phone, first_name, last_name, is_active, dashboard_created_at, profile_created_at, email_verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $email !== '' ? $email : null,
            $passwordHash,
            $phone !== '' ? $phone : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            isset($fields['is_active']) && !$fields['is_active'] ? 0 : 1,
            !empty($fields['email_verified_at']) ? date('Y-m-d H:i:s') : null,
        ]);
        $id = (int) $pdo->lastInsertId();
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
        return $id;
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE users SET role_id = ?, organisation_id = ?, email = ?, phone = ?, first_name = ?, last_name = ?, is_active = ? WHERE id = ?'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $fields['email'] !== '' ? $fields['email'] : null,
            $fields['phone'] !== '' ? $fields['phone'] : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            $id,
        ]);
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
    }

    public static function markEmailVerified(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
            ->execute([$id]);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([$id]);
    }

    public static function verifyPassword(string $email, string $password): ?array
    {
        $user = self::findByIdentifier('email', strtolower(trim($email)));
        if (!$user || empty($user['has_password'])) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $hash = (string) $stmt->fetchColumn();
        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }
        return $user;
    }

    public static function passwordError(string $password, string $confirm = ''): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if ($confirm !== '' && $password !== $confirm) {
            return 'Password confirmation does not match.';
        }
        return null;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    public static function hydrate(array $row): array
    {
        $managed = $row['managed_role_slugs'] ?? '[]';
        if (is_string($managed)) {
            $managed = json_decode($managed, true) ?: [];
        }
        $row['managed_role_slugs'] = array_values(array_filter((array) $managed));
        $row['has_admin_features'] = !empty($row['has_admin_features']);
        $row['is_under_organisation'] = !empty($row['is_under_organisation']);
        $row['can_manage_users'] = !empty($row['can_manage_users']);
        $row['has_password'] = !empty($row['password_hash']);
        unset($row['password_hash']);
        return $row;
    }
}
