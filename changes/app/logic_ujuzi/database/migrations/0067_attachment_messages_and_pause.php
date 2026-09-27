<?php

return [
    'up' => static function (PDO $pdo): void {
        // "Paused" = on hold: the reviewer is waiting on something (usually an
        // answer from the student) before deciding.
        $pdo->exec("ALTER TABLE attachment_applications
            MODIFY status ENUM('pending','accepted','paused','completed','recommended','rejected') NOT NULL DEFAULT 'pending'");
        $pdo->exec('ALTER TABLE attachment_applications ADD COLUMN paused_at TIMESTAMP NULL DEFAULT NULL AFTER accepted_at');
        $pdo->exec('ALTER TABLE attachment_applications ADD COLUMN letter_sent_at TIMESTAMP NULL DEFAULT NULL AFTER recommended_at');

        // Notes between the reviewer (branch admin, or the provider itself when
        // it has no branches) and the student, on one attachment request.
        $pdo->exec("CREATE TABLE IF NOT EXISTS attachment_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            sender_user_id INT UNSIGNED NOT NULL,
            sender_side ENUM('student','reviewer') NOT NULL,
            body TEXT NOT NULL,
            status_change VARCHAR(20) NULL,
            read_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_attachment_messages_app (application_id, created_at),
            CONSTRAINT fk_attachment_messages_app FOREIGN KEY (application_id) REFERENCES attachment_applications(id) ON DELETE CASCADE,
            CONSTRAINT fk_attachment_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // The share of a course fee a student must pay to enrol. Super Admin
        // changes it under Finance; 40% was the fixed value before.
        $pdo->exec("INSERT IGNORE INTO store_settings (setting_key, setting_value) VALUES ('min_first_payment_percent', '40')");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS attachment_messages');
        $pdo->exec("UPDATE attachment_applications SET status = 'pending' WHERE status = 'paused'");
        $pdo->exec('ALTER TABLE attachment_applications DROP COLUMN letter_sent_at');
        $pdo->exec('ALTER TABLE attachment_applications DROP COLUMN paused_at');
        $pdo->exec("ALTER TABLE attachment_applications
            MODIFY status ENUM('pending','accepted','completed','recommended','rejected') NOT NULL DEFAULT 'pending'");
        $pdo->exec("DELETE FROM store_settings WHERE setting_key = 'min_first_payment_percent'");
    },
];
