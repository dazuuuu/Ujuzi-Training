<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE roles SET name = 'Branch Admin (Attachment)' WHERE slug = 'branch_admin'");

        // A receipt for every course / module payment, emailed to the student
        // and kept in their payment history.
        $pdo->exec("CREATE TABLE IF NOT EXISTS payment_receipts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            receipt_no VARCHAR(40) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NULL,
            description VARCHAR(255) NOT NULL,
            amount_ksh DECIMAL(12,2) NOT NULL,
            kind ENUM('deposit','course_payment') NOT NULL DEFAULT 'course_payment',
            reference VARCHAR(100) NULL,
            emailed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_receipt_no (receipt_no),
            KEY idx_receipts_user (user_id, created_at),
            CONSTRAINT fk_receipt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Organisations ask; Super Admin deletes. One request per thing, and it can't be withdrawn.
        $pdo->exec("CREATE TABLE IF NOT EXISTS deletion_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            entity_type VARCHAR(30) NOT NULL,
            entity_id INT UNSIGNED NOT NULL,
            label VARCHAR(255) NOT NULL,
            organisation_name VARCHAR(190) NULL,
            requested_by_user_id INT UNSIGNED NOT NULL,
            reason VARCHAR(500) NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            decided_by_admin_id INT UNSIGNED NULL,
            decided_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_deletion_entity (entity_type, entity_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Branch deletion requests waiting from before move to the general list.
        $pdo->exec("INSERT INTO deletion_requests (entity_type, entity_id, label, requested_by_user_id, reason, created_at)
            SELECT 'branch', r.branch_id, b.title, r.requested_by_user_id, r.reason, r.created_at
            FROM branch_deletion_requests r INNER JOIN organisation_branches b ON b.id = r.branch_id WHERE r.status = 'pending'");
        $pdo->exec("UPDATE branch_deletion_requests SET status = 'rejected' WHERE status = 'pending'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS deletion_requests');
        $pdo->exec('DROP TABLE IF EXISTS payment_receipts');
        $pdo->exec("UPDATE roles SET name = 'Attachment providing admin' WHERE slug = 'branch_admin'");
    },
];
