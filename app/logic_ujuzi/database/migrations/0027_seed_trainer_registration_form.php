<?php

return [
    'up' => static function (PDO $pdo): void {
        $exists = $pdo->query(
            "SELECT id FROM forms WHERE title = 'Trainer / Tutor / Teacher registration' LIMIT 1"
        )->fetchColumn();
        if ($exists) {
            return;
        }

        $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'trainer' LIMIT 1")->fetchColumn();
        if (!$roleId) {
            return;
        }

        $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();

        $pdo->prepare(
            'INSERT INTO forms (title, description, is_active, created_by_admin_id) VALUES (?, ?, 1, ?)'
        )->execute([
            'Trainer / Tutor / Teacher registration',
            'Trainers, tutors, and teachers complete this after creating an account. Pick the organisation(s) you want to teach for — each organisation must approve you before you appear on their dashboard.',
            $adminId ? (int) $adminId : null,
        ]);
        $formId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO form_roles (form_id, role_id) VALUES (?, ?)')
            ->execute([$formId, (int) $roleId]);

        $fields = [
            [
                'Full name',
                'full_name',
                'name',
                null,
                '',
                'Your name as it should appear to organisation admins.',
                1,
            ],
            [
                'Phone',
                'phone',
                'phone',
                null,
                '2547...',
                '',
                1,
            ],
            [
                'Organisations',
                'organisations',
                'organisation',
                json_encode(['org_mode' => 'multiple']),
                '',
                'Choose one or more organisations. Each organisation admin must approve you before you are assigned as their tutor.',
                1,
            ],
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

        if (class_exists(\App\Models\FormResponse::class)) {
            \App\Models\FormResponse::provisionForForm($formId);
        }
    },
    'down' => "DELETE FROM forms WHERE title = 'Trainer / Tutor / Teacher registration'",
];
