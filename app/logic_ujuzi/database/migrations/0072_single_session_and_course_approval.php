<?php

return [
    'up' => static function (PDO $pdo): void {
        // One sign-in at a time: the newest sign-in's token; older sessions
        // carrying a different token are signed out.
        $pdo->exec('ALTER TABLE users ADD COLUMN session_token VARCHAR(64) NULL');
        $pdo->exec('ALTER TABLE admins ADD COLUMN session_token VARCHAR(64) NULL');

        // Super Admin approves every course before students can see it.
        // Courses already live stay live (approved now).
        $pdo->exec("ALTER TABLE courses ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            ADD COLUMN approval_note VARCHAR(500) NULL,
            ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL");
        $pdo->exec("UPDATE courses SET approval_status = 'approved', approved_at = NOW()");

        // Deleting a branch needs the organisation head and Super Admin to agree.
        $pdo->exec("CREATE TABLE IF NOT EXISTS branch_deletion_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            branch_id INT UNSIGNED NOT NULL,
            requested_by_user_id INT UNSIGNED NOT NULL,
            reason VARCHAR(500) NULL,
            organisation_approved_at TIMESTAMP NULL DEFAULT NULL,
            organisation_approved_by INT UNSIGNED NULL,
            admin_approved_at TIMESTAMP NULL DEFAULT NULL,
            admin_approved_by INT UNSIGNED NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_branch_deletion_branch (branch_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS branch_deletion_requests');
        $pdo->exec('ALTER TABLE courses DROP COLUMN approval_status, DROP COLUMN approval_note, DROP COLUMN approved_at');
        $pdo->exec('ALTER TABLE admins DROP COLUMN session_token');
        $pdo->exec('ALTER TABLE users DROP COLUMN session_token');
    },
];
