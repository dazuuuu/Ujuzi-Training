<?php

namespace App\Core;

use App\Models\User;

class UserSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('ujuzi_user');
            session_start();
        }
    }

    /**
     * Signs in, and claims the account for this session only: any other
     * device signed in to the same account is signed out on its next click.
     */
    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['session_token'] = bin2hex(random_bytes(16));
        try {
            \App\Core\Database::connection()->prepare('UPDATE users SET session_token = ? WHERE id = ?')
                ->execute([$_SESSION['session_token'], $userId]);
        } catch (\Throwable $e) {
            // Before the single-session update: several sign-ins still allowed.
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function current(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = User::find((int) $_SESSION['user_id']);
        if (!$user || empty($user['is_active'])) {
            return null;
        }
        // Someone signed in to this account somewhere else after us.
        if (!empty($user['session_token']) && ($user['session_token'] !== ($_SESSION['session_token'] ?? null))) {
            $_SESSION = [];
            flashError('You were signed out because this account signed in on another device.');
            return null;
        }
        return $user;
    }

    public static function require(): array
    {
        $user = self::current();
        if (!$user) {
            header('Location: ' . Url::to('/account/login'));
            exit;
        }
        return $user;
    }
}
