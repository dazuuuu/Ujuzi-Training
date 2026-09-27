<?php

namespace App\Models;

use App\Core\Database;

class Admin
{
    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /** The first admin (from /setup) is an owner — full access, manages other admins. */
    public static function create(string $email, string $password, ?string $name = null): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO admins (email, password_hash, name, is_owner) VALUES (?, ?, ?, 1)')
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name !== '' ? $name : null]);
        return (int) $pdo->lastInsertId();
    }

    /** Another Super Admin, limited to $permissions (AdminAccess section keys) unless $owner. */
    public static function createLimited(string $email, string $password, ?string $name, array $permissions, bool $owner, int $createdBy): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO admins (email, password_hash, name, is_owner, permissions, is_active, created_by_admin_id) VALUES (?, ?, ?, ?, ?, 1, ?)'
        )->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name ?: null, $owner ? 1 : 0, json_encode(array_values($permissions)), $createdBy]);
        return (int) $pdo->lastInsertId();
    }

    public static function all(): array
    {
        return array_map([self::class, 'hydrate'], Database::connection()->query('SELECT * FROM admins ORDER BY is_owner DESC, created_at ASC, id ASC')->fetchAll());
    }

    public static function updateAccess(int $id, ?string $name, array $permissions, bool $owner, bool $active): void
    {
        Database::connection()->prepare('UPDATE admins SET name = ?, permissions = ?, is_owner = ?, is_active = ? WHERE id = ?')
            ->execute([$name ?: null, json_encode(array_values($permissions)), $owner ? 1 : 0, $active ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
    }

    /** Active owners — there must always be at least one. */
    public static function activeOwnerCount(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM admins WHERE is_owner = 1 AND is_active = 1')->fetchColumn();
    }

    /** Decodes permissions; an admin row from before the access columns existed counts as an active owner. */
    public static function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['is_owner'] = !array_key_exists('is_owner', $row) || (int) $row['is_owner'] === 1;
        $row['is_active'] = !array_key_exists('is_active', $row) || (int) $row['is_active'] === 1;
        $perms = json_decode((string) ($row['permissions'] ?? ''), true);
        $row['permissions'] = is_array($perms) ? array_values(array_map('strval', $perms)) : [];
        return $row;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::connection()
            ->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}
