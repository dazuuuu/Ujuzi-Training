<?php

namespace App\Core;

use App\Models\Admin;

class AdminSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('pentagon_admin');
            session_start();
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        $admin = Admin::findByEmail($email);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            return true;
        }
        return false;
    }

    public static function loginAdmin(int $id, string $email): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_email'] = $email;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function current(): ?array
    {
        if (empty($_SESSION['admin_id'])) {
            return null;
        }
        return ['id' => $_SESSION['admin_id'], 'email' => $_SESSION['admin_email']];
    }

    /** Redirects to the admin login route if no admin is signed in. */
    public static function require(): array
    {
        $admin = self::current();
        if (!$admin) {
            header('Location: ' . Url::to('/admin/login'));
            exit;
        }
        return $admin;
    }
}
