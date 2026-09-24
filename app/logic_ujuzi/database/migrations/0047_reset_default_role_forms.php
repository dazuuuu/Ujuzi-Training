<?php

return [
    'up' => static function (PDO $pdo): void {
        // Wipe every existing form (and its fields/role assignments/collected answers).
        // The four templates below become the new defaults; Super Admin can still
        // edit/add/remove fields on them afterwards from Admin -> Forms.
        $pdo->exec('DELETE FROM form_responses');
        $pdo->exec('DELETE FROM form_fields');
        $pdo->exec('DELETE FROM form_roles');
        $pdo->exec('DELETE FROM forms');
        try {
            $pdo->exec('ALTER TABLE forms AUTO_INCREMENT = 1');
            $pdo->exec('ALTER TABLE form_fields AUTO_INCREMENT = 1');
        } catch (\Throwable $e) {
            // Non-MySQL or restricted permissions - ignore, IDs simply keep counting up.
        }

        $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
        $adminId = $adminId ? (int) $adminId : null;

        $roleIdBySlug = [];
        $rows = $pdo->query("SELECT id, slug FROM roles WHERE slug IN ('organisation_admin','attachment_trainer','trainer','student')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $roleIdBySlug[$row['slug']] = (int) $row['id'];
        }

        $insertForm = $pdo->prepare(
            'INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, 1, ?, ?)'
        );
        $insertField = $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertRole = $pdo->prepare('INSERT INTO form_roles (form_id, role_id) VALUES (?, ?)');

        $makeForm = static function (string $title, string $description, array $fields, ?int $roleId) use ($pdo, $insertForm, $insertField, $insertRole, $adminId): void {
            if (!$roleId) {
                return;
            }
            $insertForm->execute([$title, $description, 'profile', $adminId]);
            $formId = (int) $pdo->lastInsertId();
            foreach ($fields as $index => $field) {
                $insertField->execute([
                    $formId,
                    $field[0],
                    $field[1],
                    $field[2],
                    $field[3] ?? null,
                    ($field[4] ?? '') !== '' ? $field[4] : null,
                    ($field[5] ?? '') !== '' ? $field[5] : null,
                    $field[6] ?? 1,
                    $index,
                ]);
            }
            $insertRole->execute([$formId, $roleId]);
            if (class_exists(\App\Models\FormResponse::class)) {
                \App\Models\FormResponse::provisionForForm($formId);
            }
        };

        // 1. Organisation providing Courses
        $makeForm(
            'Organisation providing Courses',
            'Tell us about the organisation that will be publishing and running courses on the platform.',
            [
                ['Business name', 'business_name', 'text', null, 'e.g. Acme Skills Institute', '', 1],
                ['Email', 'email', 'email', null, '', '', 1],
                ['Phone number', 'phone', 'phone', null, '2547...', '', 1],
                ['Location', 'location', 'text', null, 'e.g. Nairobi, Westlands', '', 1],
                ['Country of origin', 'country', 'country', null, '', '', 1],
                ['KRA pin', 'kra_pin', 'text', null, 'Optional', 'Optional - leave blank if not available yet.', 0],
                ['Branches', 'branches', 'branches', null, '', 'Add every branch this organisation operates from, with its location.', 0],
            ],
            $roleIdBySlug['organisation_admin'] ?? null
        );

        // 2. Organisation providing Attachments
        $makeForm(
            'Organisation providing Attachments',
            'Tell us about the organisation that will be hosting students for attachment/industrial training. Branch admins can be assigned per branch from the Branches page to approve attachees.',
            [
                ['Business name', 'business_name', 'text', null, 'e.g. Acme Attachment Hub', '', 1],
                ['Email', 'email', 'email', null, '', '', 1],
                ['Phone number', 'phone', 'phone', null, '2547...', '', 1],
                ['Location', 'location', 'text', null, 'e.g. Nairobi, Industrial Area', '', 1],
                ['Country of origin', 'country', 'country', null, '', '', 1],
                ['KRA pin', 'kra_pin', 'text', null, 'Optional', 'Optional - leave blank if not available yet.', 0],
                ['Branches', 'branches', 'branches', null, '', 'Add every branch that can host attachees, with its location. Assign a branch admin from the Branches page.', 0],
            ],
            $roleIdBySlug['attachment_trainer'] ?? null
        );

        // 3. Tutor registration
        $makeForm(
            'Tutor registration',
            'Trainer accounts are created by the organisation. This profile confirms the trainer\'s details.',
            [
                ['Full name', 'full_name', 'name', null, '', 'First name, last name, and any other names.', 1],
                ['Other names', 'other_names', 'text', null, 'Optional', 'Any middle or additional names not covered above.', 0],
                ['Phone number', 'phone', 'phone', null, '2547...', '', 1],
                ['Country of origin', 'country', 'country', null, '', '', 1],
                ['Email address', 'email', 'email', null, '', 'Automatically filled from the account email.', 1],
            ],
            $roleIdBySlug['trainer'] ?? null
        );

        // 4. Student registration
        $makeForm(
            'Student registration',
            'Complete your profile to access courses and attachment opportunities.',
            [
                ['Email address', 'email', 'email', null, '', 'Automatically filled in if your organisation already added you.', 1],
                ['Full name', 'full_name', 'name', null, '', 'First name, last name, and any other names.', 1],
                ['Other names', 'other_names', 'text', null, 'Optional', 'Any middle or additional names not covered above.', 0],
                ['Country of origin', 'country', 'country', null, '', '', 1],
                ['Phone number', 'phone', 'phone', null, '2547...', '', 1],
                ['Organisation', 'student_organisation', 'organisation', null, '', 'Select the organisation providing your course. They must approve you before you can see their courses.', 0],
                ['Attachment provider', 'student_attachment_provider', 'attachment_provider', null, '', 'Select an organisation offering attachment placements.', 0],
                ['Attachment branch', 'student_attachment_branch', 'branch_select', null, '', 'Select the branch. The branch admin must approve you.', 0],
                ['License / service number', 'license_no', 'text', null, 'Optional', '', 0],
            ],
            $roleIdBySlug['student'] ?? null
        );
    },
    'down' => "DELETE FROM forms WHERE title IN ('Organisation providing Courses','Organisation providing Attachments','Tutor registration','Student registration')",
];
