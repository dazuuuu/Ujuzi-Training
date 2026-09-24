<?php

return [
    'up' => static function (PDO $pdo): void {
        $columnExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnExists->execute(['organisation_categories', 'is_open']);
        if ((int) $columnExists->fetchColumn() === 0) {
            // Open = every approved student of the organisation sees courses in it.
            // Not open = only students specifically approved for that category do.
            $pdo->exec('ALTER TABLE organisation_categories ADD is_open TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        }

        $pdo->exec("UPDATE roles SET name = 'Tutors Creating Courses Portal' WHERE slug = 'trainer'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE roles SET name = 'Tutor' WHERE slug = 'trainer'");
        try {
            $pdo->exec('ALTER TABLE organisation_categories DROP COLUMN is_open');
        } catch (Throwable $e) {
        }
    },
];
