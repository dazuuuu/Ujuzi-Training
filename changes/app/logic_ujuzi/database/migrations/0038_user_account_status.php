<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE users ADD account_status ENUM('active','blocked','suspended') NOT NULL DEFAULT 'active' AFTER is_active");
        } catch (Throwable $e) {
            // Column already exists.
        }
        $pdo->exec("UPDATE users SET account_status = CASE WHEN is_active = 1 THEN 'active' ELSE 'blocked' END");
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN account_status');
        } catch (Throwable $e) {
        }
    },
];
