<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE courses ADD COLUMN visibility ENUM('strict','global') NOT NULL DEFAULT 'strict' AFTER is_published");
        } catch (Throwable $e) {
            // Column already exists.
        }

        try {
            $pdo->exec('ALTER TABLE course_modules ADD COLUMN quiz_questions JSON NULL AFTER materials');
        } catch (Throwable $e) {
            // Column already exists.
        }

        try {
            $pdo->exec('ALTER TABLE course_modules ADD COLUMN pass_percent TINYINT UNSIGNED NOT NULL DEFAULT 70 AFTER quiz_questions');
        } catch (Throwable $e) {
            // Column already exists.
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS organisation_branches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organisation_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            location VARCHAR(255) NOT NULL,
            cover_image VARCHAR(500) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_org_branches_org (organisation_id),
            CONSTRAINT fk_org_branches_org FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS course_module_progress (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            module_id INT UNSIGNED NOT NULL,
            score TINYINT UNSIGNED NOT NULL DEFAULT 0,
            passed TINYINT(1) NOT NULL DEFAULT 0,
            answers JSON NULL,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_module_progress (user_id, module_id),
            KEY idx_progress_course (course_id, user_id),
            CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_progress_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            CONSTRAINT fk_progress_module FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $studentRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'student' LIMIT 1")->fetchColumn();
        if (!$studentRoleId) {
            return;
        }

        try {
            $hasOrgField = $pdo->prepare(
                "SELECT f.id FROM forms f
                 INNER JOIN form_roles fr ON fr.form_id = f.id
                 INNER JOIN form_fields ff ON ff.form_id = f.id
                 WHERE fr.role_id = ? AND ff.field_type = 'organisation'
                 LIMIT 1"
            );
            $hasOrgField->execute([(int) $studentRoleId]);
            if ($hasOrgField->fetchColumn()) {
                return;
            }

            $exists = $pdo->query("SELECT id FROM forms WHERE title = 'Student organisation' LIMIT 1")->fetchColumn();
            if ($exists) {
                $link = $pdo->prepare('INSERT IGNORE INTO form_roles (form_id, role_id) VALUES (?, ?)');
                $link->execute([(int) $exists, (int) $studentRoleId]);
                return;
            }

            $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, 1, ?, ?)'
            )->execute([
                'Student organisation',
                'Pick the organisation you are learning with. Your dashboard then shows that organisation’s courses, plus any global courses such as basic skills.',
                'profile',
                $adminId ? (int) $adminId : null,
            ]);
            $formId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT IGNORE INTO form_roles (form_id, role_id) VALUES (?, ?)')
                ->execute([$formId, (int) $studentRoleId]);
            $pdo->prepare(
                'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $formId,
                'Organisation',
                'student_organisation',
                'organisation',
                json_encode(['org_mode' => 'single']),
                '',
                'Courses on your dashboard come from this organisation. Global courses stay visible to every student.',
                1,
                0,
            ]);
        } catch (Throwable $e) {
            // Forms table may not have purpose yet on older installs.
        }
    },
    'down' => "DROP TABLE IF EXISTS course_module_progress; DROP TABLE IF EXISTS organisation_branches;",
];
