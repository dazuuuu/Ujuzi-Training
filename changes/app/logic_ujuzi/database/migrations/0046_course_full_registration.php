<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE courses ADD COLUMN requires_full_registration TINYINT(1) NOT NULL DEFAULT 0 AFTER certificate_enabled");
        } catch (Throwable $e) {
            // Column already exists.
        }
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE courses DROP COLUMN requires_full_registration");
        } catch (Throwable $e) {
            // Ignore.
        }
    },
];
