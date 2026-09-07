<?php

return [
    'up' => static function (PDO $pdo): void {
        $table = 'organisation_branches';
        $columnExists = static function (string $column) use ($pdo, $table): bool {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
            );
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        };

        try {
            $pdo->exec('ALTER TABLE organisation_branches MODIFY organisation_id INT UNSIGNED NULL');
        } catch (Throwable $e) {
            // The column may already be nullable on an older installation.
        }
        if (!$columnExists('owner_type')) {
            $pdo->exec("ALTER TABLE organisation_branches ADD owner_type ENUM('organisation','attachment_provider') NOT NULL DEFAULT 'organisation' AFTER organisation_id");
        }
        if (!$columnExists('owner_user_id')) {
            $pdo->exec('ALTER TABLE organisation_branches ADD owner_user_id INT UNSIGNED NULL AFTER owner_type');
        }
        try {
            $pdo->exec('ALTER TABLE organisation_branches ADD INDEX idx_org_branches_owner (owner_type, owner_user_id)');
        } catch (Throwable $e) {
            // Index already exists.
        }
        $pdo->exec("UPDATE organisation_branches SET owner_type = 'organisation' WHERE owner_type IS NULL OR owner_type = ''");

        $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'attachment_trainer' LIMIT 1")->fetchColumn();
        if (!$roleId) {
            return;
        }
        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Attachment Trainer registration' LIMIT 1")->fetchColumn();
        if (!$formId) {
            return;
        }

        $findField = static function (string $type) use ($pdo, $formId) {
            $stmt = $pdo->prepare('SELECT id FROM form_fields WHERE form_id = ? AND field_type = ? ORDER BY id ASC LIMIT 1');
            $stmt->execute([(int) $formId, $type]);
            return $stmt->fetchColumn();
        };
        $insert = $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $organisationField = $findField('organisation');
        if (!$organisationField) {
            $insert->execute([
                (int) $formId,
                'Organisation',
                'attachment_organisation',
                'organisation',
                json_encode(['org_mode' => 'single']),
                null,
                'Choose the organisation this attachment provider belongs to.',
                1,
                2,
            ]);
        }

        $branchesField = $findField('branches');
        if (!$branchesField) {
            $insert->execute([
                (int) $formId,
                'Create branches',
                'attachment_provider_branches',
                'branches',
                null,
                null,
                'Add every branch. Name and location are required on each row.',
                1,
                3,
            ]);
        } else {
            $pdo->prepare('UPDATE form_fields SET label = ?, help_text = ? WHERE id = ?')->execute([
                'Create branches',
                'Add every branch. Name and location are required on each row.',
                (int) $branchesField,
            ]);
        }

        $branchField = $findField('branch_select');
        if ($branchField) {
            $pdo->prepare('UPDATE form_fields SET label = ?, help_text = ? WHERE id = ?')->execute([
                'Branch',
                'Choose a saved branch after selecting an organisation or attachment provider.',
                (int) $branchField,
            ]);
        }

        $pdo->prepare("UPDATE form_fields SET sort_order = CASE field_type
            WHEN 'name' THEN 0
            WHEN 'phone' THEN 1
            WHEN 'organisation' THEN 2
            WHEN 'branches' THEN 3
            WHEN 'branch_select' THEN 4
            WHEN 'duration' THEN 5
            ELSE sort_order END
            WHERE form_id = ?")->execute([(int) $formId]);
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE organisation_branches DROP INDEX idx_org_branches_owner');
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec('ALTER TABLE organisation_branches DROP COLUMN owner_user_id');
            $pdo->exec('ALTER TABLE organisation_branches DROP COLUMN owner_type');
        } catch (Throwable $e) {
        }
    },
];
