<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec("ALTER TABLE forms ADD COLUMN purpose VARCHAR(20) NOT NULL DEFAULT 'profile' AFTER is_active");
        } catch (Throwable $e) {
            // Column already exists.
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS organisation_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organisation_id INT UNSIGNED NOT NULL,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_org_category_slug (organisation_id, slug),
            KEY idx_org_cat_org (organisation_id),
            CONSTRAINT fk_org_cat_org FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            organisation_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            trainer_user_id INT UNSIGNED NOT NULL,
            form_id INT UNSIGNED NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            cover_image VARCHAR(500) NULL,
            materials JSON NULL,
            answers JSON NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_courses_org (organisation_id),
            KEY idx_courses_trainer (trainer_user_id),
            CONSTRAINT fk_courses_org FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
            CONSTRAINT fk_courses_category FOREIGN KEY (category_id) REFERENCES organisation_categories(id) ON DELETE RESTRICT,
            CONSTRAINT fk_courses_trainer FOREIGN KEY (trainer_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_courses_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS course_modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_id INT UNSIGNED NOT NULL,
            title VARCHAR(191) NOT NULL,
            description TEXT NULL,
            summary TEXT NULL,
            notes TEXT NULL,
            duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
            video_source ENUM('upload','youtube') NOT NULL DEFAULT 'upload',
            video_path VARCHAR(500) NULL,
            video_url VARCHAR(500) NULL,
            materials JSON NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_modules_course (course_id),
            CONSTRAINT fk_modules_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $exists = $pdo->query("SELECT id FROM forms WHERE title = 'Create a course' LIMIT 1")->fetchColumn();
        $roleIds = $pdo->query("SELECT id FROM roles WHERE slug IN ('trainer', 'attachment_trainer')")->fetchAll(PDO::FETCH_COLUMN);
        $formId = $exists ? (int) $exists : 0;

        if (!$formId) {
            if (!$roleIds) {
                return;
            }
            $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, 1, ?, ?)'
            )->execute([
                'Create a course',
                'Approved trainers use this form to create a course for an organisation they belong to. Pick a category that organisation offers, then add module videos after the course is saved.',
                'course',
                $adminId ? (int) $adminId : null,
            ]);
            $formId = (int) $pdo->lastInsertId();
        }

        $linkRole = $pdo->prepare('INSERT IGNORE INTO form_roles (form_id, role_id) VALUES (?, ?)');
        foreach ($roleIds as $roleId) {
            $linkRole->execute([$formId, (int) $roleId]);
        }

        if ($exists) {
            return;
        }

        $fields = [
            ['Course title', 'course_title', 'text', null, 'e.g. Introduction to First Aid', '', 1],
            ['Course description', 'course_description', 'paragraph', null, '', 'What learners will gain from this course.', 1],
            ['Category', 'course_category', 'category', null, '', 'Only categories from organisations that have approved you are listed.', 1],
            ['Cover image', 'course_cover', 'image', null, '', 'Shown on the course card.', 0],
            ['Course materials', 'course_materials', 'files', null, '', 'Optional PDFs, images, or Word documents for the whole course.', 0],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($fields as $index => $field) {
            $stmt->execute([
                $formId,
                $field[0],
                $field[1],
                $field[2],
                $field[3],
                $field[4] !== '' ? $field[4] : null,
                $field[5] !== '' ? $field[5] : null,
                $field[6],
                $index,
            ]);
        }
    },
    'down' => "DROP TABLE IF EXISTS course_modules; DROP TABLE IF EXISTS courses; DROP TABLE IF EXISTS organisation_categories;",
];
