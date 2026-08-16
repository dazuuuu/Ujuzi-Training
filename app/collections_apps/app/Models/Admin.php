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

    public static function create(string $email, string $password): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)')
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        return (int) $pdo->lastInsertId();
    }
}
