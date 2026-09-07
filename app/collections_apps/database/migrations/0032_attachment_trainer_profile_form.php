<?php

return [
    'up' => static function (PDO $pdo): void {
        $attachmentRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'attachment_trainer' LIMIT 1")->fetchColumn();
        $trainerRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'trainer' LIMIT 1")->fetchColumn();
        if (!$attachmentRoleId) {
            return;
        }

        if ($trainerRoleId) {
            $stmt = $pdo->prepare(
                "DELETE fr FROM form_roles fr
                 INNER JOIN forms f ON f.id = fr.form_id
                 WHERE fr.role_id = ? AND LOWER(f.title) LIKE '%trainer / tutor / teacher registration%'"
            );
            $stmt->execute([(int) $attachmentRoleId]);
        }

        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Attachment Trainer registration' LIMIT 1")->fetchColumn();
        if (!$formId) {
            $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, 1, ?, ?)'
            )->execute([
                'Attachment Trainer registration',
                'Attachment trainers complete this profile. Pick the saved branch where students can report and set the attachment period.',
                'profile',
                $adminId ? (int) $adminId : null,
            ]);
            $formId = (int) $pdo->lastInsertId();

            $fields = [
                ['Full name', 'full_name', 'name', null, '', 'Your name as it should appear to students.', 1],
                ['Phone', 'phone', 'phone', null, '2547...', '', 1],
                ['Branch', 'branch', 'branch_select', null, '', 'Choose from branches already created for your organisation.', 1],
                ['Attachment duration', 'attachment_duration', 'duration', null, '', 'Enter how long the attachment lasts, such as 1 week, 2 weeks, 1 month, or 1 year.', 1],
            ];

            $insertField = $pdo->prepare(
                'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($fields as $index => $field) {
                $insertField->execute([
                    (int) $formId,
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

        $pdo->prepare('DELETE FROM form_roles WHERE form_id = ?')->execute([(int) $formId]);
        $pdo->prepare('INSERT IGNORE INTO form_roles (form_id, role_id) VALUES (?, ?)')
            ->execute([(int) $formId, (int) $attachmentRoleId]);

        if (class_exists(\App\Models\FormResponse::class)) {
            \App\Models\FormResponse::provisionForForm((int) $formId);
        }
    },
    'down' => "DELETE FROM forms WHERE title = 'Attachment Trainer registration'",
];
