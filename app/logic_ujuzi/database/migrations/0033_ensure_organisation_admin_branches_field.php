<?php

return [
    'up' => static function (PDO $pdo): void {
        $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'organisation_admin' LIMIT 1")->fetchColumn();
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

        $existing = $pdo->prepare("SELECT id FROM form_fields WHERE form_id = ? AND field_type = 'branches' LIMIT 1");
        $existing->execute([(int) $formId]);
        $fieldId = $existing->fetchColumn();

        if ($fieldId) {
            $pdo->prepare(
                "UPDATE form_fields
                 SET label = ?, help_text = COALESCE(NULLIF(help_text, ''), ?)
                 WHERE id = ?"
            )->execute([
                'Create branches',
                'Add every branch. Name and location are required on each row.',
                (int) $fieldId,
            ]);
            return;
        }

        $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM form_fields WHERE form_id = ?');
        $sortStmt->execute([(int) $formId]);

        $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $formId,
            'Create branches',
            'organisation_branches',
            'branches',
            null,
            null,
            'Add every branch. Name and location are required on each row.',
            1,
            (int) $sortStmt->fetchColumn(),
        ]);
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'organisation_branches' AND field_type = 'branches'");
    },
];
