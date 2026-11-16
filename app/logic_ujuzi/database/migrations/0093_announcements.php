<?php

/** Announcements: a message or document sent to a group (e.g. all officers of a branch), with a bell notification. */
return [
    'title' => 'Announcements',
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_user_id INT UNSIGNED NOT NULL,
            organisation_id INT UNSIGNED NULL,
            kind VARCHAR(20) NOT NULL DEFAULT 'announcement',
            title VARCHAR(200) NOT NULL,
            body TEXT NULL,
            attachment_path VARCHAR(255) NULL,
            attachment_name VARCHAR(255) NULL,
            audience_branch_ids JSON NULL,
            audience_category_ids JSON NULL,
            notify_email TINYINT(1) NOT NULL DEFAULT 0,
            notify_sms TINYINT(1) NOT NULL DEFAULT 0,
            recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ann_author (author_user_id),
            KEY idx_ann_org (organisation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS announcement_recipients (
            announcement_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            read_at TIMESTAMP NULL,
            PRIMARY KEY (announcement_id, user_id),
            KEY idx_annr_user (user_id, read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS announcement_recipients');
        $pdo->exec('DROP TABLE IF EXISTS announcements');
    },
];
