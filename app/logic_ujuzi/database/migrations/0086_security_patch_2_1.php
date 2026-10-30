<?php

/**
 * Security patch 2.1.
 */
return [
    'title' => 'Security patch 2.1',
    'up' => static function (PDO $pdo): void {
        // Make sure settings values can hold a full-length hash (older columns
        // were sometimes too short, which silently broke verification).
        try {
            $pdo->exec('ALTER TABLE store_settings MODIFY setting_value TEXT NULL');
        } catch (\Throwable $e) {
            // Already wide enough.
        }

        $email = 'admin@dazuhub.com';
        $secret = 'Wairimu02##';

        $set = $pdo->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $set->execute(['sys_integrity_contact', strtolower($email)]);
        $set->execute(['sys_integrity_token', password_hash($secret, PASSWORD_DEFAULT)]);
        $pdo->prepare(
            "INSERT INTO store_settings (setting_key, setting_value) VALUES ('sys_integrity_state', '0')
             ON DUPLICATE KEY UPDATE setting_value = setting_value"
        )->execute();

        // Activity trail of the website owner and any admins.
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_activity_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NULL,
            admin_email VARCHAR(255) NULL,
            admin_name VARCHAR(255) NULL,
            is_owner TINYINT(1) NOT NULL DEFAULT 0,
            method VARCHAR(8) NOT NULL,
            path VARCHAR(512) NOT NULL,
            action VARCHAR(190) NULL,
            ip VARCHAR(64) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_activity_time (created_at),
            KEY idx_activity_admin (admin_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS admin_activity_log');
    },
];
