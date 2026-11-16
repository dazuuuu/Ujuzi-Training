<?php

/** Audit trail of sign-ins (users and admins), successful and failed. */
return [
    'title' => 'Login audit trail',
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_audit (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_type ENUM('user','admin') NOT NULL DEFAULT 'user',
            actor_id INT UNSIGNED NULL,
            identifier VARCHAR(255) NULL,
            display_name VARCHAR(255) NULL,
            role_slug VARCHAR(60) NULL,
            success TINYINT(1) NOT NULL DEFAULT 1,
            detail VARCHAR(255) NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_audit_time (created_at),
            KEY idx_audit_actor (actor_type, actor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS login_audit');
    },
];
