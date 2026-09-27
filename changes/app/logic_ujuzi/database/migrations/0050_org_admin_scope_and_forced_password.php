<?php

return [
    'up' => static function (PDO $pdo): void {
        // An organisation admin manages only the students and tutors under
        // their organisation. Attachment providers/branch admins belong to a
        // completely different organisation and must not be creatable here.
        $pdo->exec("UPDATE roles SET managed_role_slugs = '[\"trainer\",\"student\"]' WHERE slug = 'organisation_admin'");

        $columnExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnExists->execute(['users', 'must_change_password']);
        if ((int) $columnExists->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE users ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash');
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE roles SET managed_role_slugs = '[\"trainer\",\"attachment_trainer\",\"student\"]' WHERE slug = 'organisation_admin'");
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN must_change_password');
        } catch (Throwable $e) {
        }
    },
];
