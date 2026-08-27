<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE organisation_branches ADD COLUMN details JSON NULL AFTER location');
        } catch (Throwable $e) {
            // Column already exists.
        }
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE organisation_branches DROP COLUMN details');
        } catch (Throwable $e) {
            // Column already dropped.
        }
    },
];
