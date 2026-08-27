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

    public static function create(string $email, string $password, ?string $name = null): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO admins (email, password_hash, name) VALUES (?, ?, ?)')
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name !== '' ? $name : null]);
        return (int) $pdo->lastInsertId();
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
