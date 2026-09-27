<?php

return [
    'up' => static function (PDO $pdo): void {
        // "Organisation" and "Attachment provider" selection move off the profile
        // registration form onto their own side-nav pages (Account\CourseOrganisationController
        // and Account\AttachmentController::index), each with a proper approval workflow.
        $pdo->exec("DELETE FROM form_fields WHERE field_key IN ('student_organisation', 'student_attachment_provider', 'student_attachment_branch')");

        $columnExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnExists->execute(['organisation_memberships', 'branch_id']);
        if ((int) $columnExists->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE organisation_memberships ADD branch_id INT UNSIGNED NULL AFTER organisation_id');
            try {
                $pdo->exec(
                    'ALTER TABLE organisation_memberships
                     ADD CONSTRAINT fk_om_branch FOREIGN KEY (branch_id) REFERENCES organisation_branches(id) ON DELETE SET NULL'
                );
            } catch (Throwable $e) {
                // Constraint may already exist, or engine doesn't support it — column still works without it.
            }
        }
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE organisation_memberships DROP FOREIGN KEY fk_om_branch');
        } catch (Throwable $e) {
        }
        try {
            $pdo->exec('ALTER TABLE organisation_memberships DROP COLUMN branch_id');
        } catch (Throwable $e) {
        }
    },
];
