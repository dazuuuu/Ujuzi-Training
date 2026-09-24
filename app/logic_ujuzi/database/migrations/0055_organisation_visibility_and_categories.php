<?php

return [
    'up' => static function (PDO $pdo): void {
        $columnExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );

        $columnExists->execute(['organisations', 'visible_to_students']);
        if ((int) $columnExists->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE organisations ADD visible_to_students TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active');
        }

        $columnExists->execute(['organisation_memberships', 'category_ids']);
        if ((int) $columnExists->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE organisation_memberships ADD category_ids JSON NULL AFTER branch_id');
        }
    },
    'down' => static function (PDO $pdo): void {
        foreach ([
            ['organisations', 'visible_to_students'],
            ['organisation_memberships', 'category_ids'],
        ] as [$table, $column]) {
            try {
                $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
            } catch (Throwable $e) {
            }
        }
    },
];
