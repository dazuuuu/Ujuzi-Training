<?php

return [
    'up' => static function (PDO $pdo): void {
        foreach ([
            "ALTER TABLE courses ADD COLUMN requires_attachment TINYINT(1) NOT NULL DEFAULT 0 AFTER enrollment_fee_ksh",
            "ALTER TABLE courses ADD COLUMN certificate_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER requires_attachment",
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Column already exists.
            }
        }

        foreach ([
            "ALTER TABLE organisation_branches ADD COLUMN branch_admin_user_id INT UNSIGNED NULL AFTER owner_user_id",
            "ALTER TABLE organisation_branches ADD COLUMN branch_admin_name VARCHAR(191) NULL AFTER branch_admin_user_id",
            "ALTER TABLE organisation_branches ADD COLUMN branch_admin_email VARCHAR(191) NULL AFTER branch_admin_name",
            "ALTER TABLE organisation_branches ADD COLUMN branch_admin_phone VARCHAR(50) NULL AFTER branch_admin_email",
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Column already exists.
            }
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS attachment_applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            provider_user_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            status ENUM('pending','accepted','completed','recommended','rejected') NOT NULL DEFAULT 'pending',
            provider_note TEXT NULL,
            selected_at TIMESTAMP NULL DEFAULT NULL,
            accepted_at TIMESTAMP NULL DEFAULT NULL,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            recommended_at TIMESTAMP NULL DEFAULT NULL,
            rejected_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_attachment_student_course (student_user_id, course_id),
            KEY idx_attachment_provider_status (provider_user_id, status),
            KEY idx_attachment_branch_status (branch_id, status),
            CONSTRAINT fk_attachment_student FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_attachment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            CONSTRAINT fk_attachment_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_attachment_branch FOREIGN KEY (branch_id) REFERENCES organisation_branches(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS attachment_applications');
    },
];
