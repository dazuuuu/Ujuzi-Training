<?php

return [
    'up' => static function (PDO $pdo): void {
        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Create a course' LIMIT 1")->fetchColumn();

        if (!$formId) {
            $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, 1, ?, ?)'
            )->execute([
                'Create a course',
                'Tutors use this form to create a course for the organisation that approved them.',
                'course',
                $adminId ? (int) $adminId : null,
            ]);
            $formId = (int) $pdo->lastInsertId();

            $fields = [
                ['Course title', 'course_title', 'text', null, 'e.g. Introduction to First Aid', '', 1],
                ['Course description', 'course_description', 'paragraph', null, '', 'What learners will gain from this course.', 1],
                ['Cover image', 'course_cover', 'image', null, '', 'Shown on the course card.', 0],
                ['Course materials', 'course_materials', 'files', null, '', 'Optional PDFs, images, or Word documents for the whole course.', 0],
            ];
            $insert = $pdo->prepare(
                'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($fields as $index => $field) {
                $insert->execute([
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
        }

        // Make sure it's assigned to both tutor-type roles regardless of when they were created.
        $roleIds = $pdo->query("SELECT id FROM roles WHERE slug IN ('trainer', 'attachment_trainer')")->fetchAll(PDO::FETCH_COLUMN);
        $linkRole = $pdo->prepare('INSERT IGNORE INTO form_roles (form_id, role_id) VALUES (?, ?)');
        foreach ($roleIds as $roleId) {
            $linkRole->execute([(int) $formId, (int) $roleId]);
        }
    },
    'down' => static function (PDO $pdo): void {
        // Intentionally a no-op — this migration only repairs missing state.
    },
];
