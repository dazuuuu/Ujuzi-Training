<?php

namespace App\Core;

use App\Models\Admin;

class AdminSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('ujuzi_admin');
            session_start();
        }
    }

    public static function attempt(string $email, string $password): bool
    {
        $admin = Admin::findByEmail($email);
        if ($admin && password_verify($password, $admin['password_hash']) && Admin::hydrate($admin)['is_active']) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            self::claim((int) $admin['id']);
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'] ?? null;
            return true;
        }
        return false;
    }

    /** One sign-in at a time per admin account (see UserSession::login()). */
    private static function claim(int $adminId): void
    {
        $_SESSION['admin_session_token'] = bin2hex(random_bytes(16));
        try {
            \App\Core\Database::connection()->prepare('UPDATE admins SET session_token = ? WHERE id = ?')
                ->execute([$_SESSION['admin_session_token'], $adminId]);
        } catch (\Throwable $e) {
            // Before the single-session update.
        }
    }

    public static function loginAdmin(int $id, string $email, ?string $name = null): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $id;
        self::claim($id);
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_name'] = $name;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /**
     * The signed-in admin, re-read from the database once per request so a
     * change to their access — or deactivating them — applies straight away.
     */
    public static function current(): ?array
    {
        static $loaded = null;
        if (empty($_SESSION['admin_id'])) {
            return null;
        }
        if ($loaded !== null && $loaded['id'] === (int) $_SESSION['admin_id']) {
            return $loaded;
        }
        $row = Admin::find((int) $_SESSION['admin_id']);
        if (!$row || !Admin::hydrate($row)['is_active']) {
            unset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_name']);
            return null;
        }
        if (!empty($row['session_token']) && $row['session_token'] !== ($_SESSION['admin_session_token'] ?? null)) {
            unset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_name'], $_SESSION['admin_session_token']);
            flashError('You were signed out because this admin account signed in on another device.');
            return null;
        }
        $row = Admin::hydrate($row);
        $loaded = [
            'id' => $row['id'],
            'email' => $row['email'],
            'name' => $row['name'] ?? null,
            'is_super_admin' => true,
            'is_owner' => $row['is_owner'],
            'permissions' => $row['permissions'],
        ];
        return $loaded;
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
