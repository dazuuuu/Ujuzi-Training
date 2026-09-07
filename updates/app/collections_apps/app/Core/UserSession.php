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

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
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
