<?php

return [
    'up' => static function (PDO $pdo): void {
        $columnExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnExists->execute(['users', 'other_names']);
        if ((int) $columnExists->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE users ADD other_names VARCHAR(191) NULL AFTER last_name');
        }
    },
    'down' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN other_names');
        } catch (Throwable $e) {
        }
    },
];
