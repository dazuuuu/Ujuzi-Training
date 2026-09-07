<?php

return [
    'up' => static function (PDO $pdo): void {
        $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'student' LIMIT 1")->fetchColumn();
        if (!$roleId) {
            return;
        }
        $formId = $pdo->query(
            "SELECT f.id
             FROM forms f
             INNER JOIN form_roles fr ON fr.form_id = f.id
             WHERE fr.role_id = " . (int) $roleId . "
               AND COALESCE(f.purpose, 'profile') = 'profile'
             ORDER BY f.id ASC
             LIMIT 1"
        )->fetchColumn();
        if (!$formId) {
            return;
        }

        $find = $pdo->prepare('SELECT id FROM form_fields WHERE form_id = ? AND field_type = ? LIMIT 1');
        $find->execute([(int) $formId, 'attachment_provider']);
        $providerField = $find->fetchColumn();
        if (!$providerField) {
            $pdo->prepare(
                'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                (int) $formId,
                'Attachment provider',
                'student_attachment_provider',
                'attachment_provider',
                null,
                null,
                'Choose an attachment provider after selecting your organisation.',
                0,
                1,
            ]);
        }

        $find->execute([(int) $formId, 'branch_select']);
        $branchField = $find->fetchColumn();
        if (!$branchField) {
            $pdo->prepare(
                'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                (int) $formId,
                'Attachment branch',
                'student_attachment_branch',
                'branch_select',
                null,
                null,
                'Choose one saved branch for the selected attachment provider, when available.',
                0,
                2,
            ]);
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM form_fields WHERE field_key IN ('student_attachment_provider', 'student_attachment_branch')");
    },
];
