<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE organisation_branches MODIFY owner_type VARCHAR(32) NOT NULL DEFAULT 'organisation'");
        } catch (Throwable $e) {
            // Older installations may already have a flexible owner column.
        }
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec("UPDATE organisation_branches SET owner_type = 'organisation' WHERE owner_type NOT IN ('organisation','attachment_provider')");
            $pdo->exec("ALTER TABLE organisation_branches MODIFY owner_type ENUM('organisation','attachment_provider') NOT NULL DEFAULT 'organisation'");
        } catch (Throwable $e) {
        }
    },
];
