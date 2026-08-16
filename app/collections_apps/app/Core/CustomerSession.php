<?php

namespace App\Core;

use App\Models\Customer;

class CustomerSession
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('pentagon_customer');
            session_start();
        }
    }

    public static function login(int $customerId): void
    {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $customerId;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function current(): ?array
    {
        if (empty($_SESSION['customer_id'])) {
            return null;
        }
        return Customer::find((int) $_SESSION['customer_id']);
    }

    public static function require(): array
    {
        $customer = self::current();
        if (!$customer) {
            header('Location: ' . Url::to('/account/login'));
            exit;
        }
        return $customer;
    }
}
