<?php

/**
 * Some hosts created tables with different collations (utf8mb4_general_ci vs
 * utf8mb4_unicode_ci), which breaks comparisons with "Illegal mix of
 * collations" — e.g. approving a course. Convert every table to the
 * database's own collation so they all match.
 */
return [
    'title' => 'Unify table collations',
    'up' => static function (PDO $pdo): void {
        $collation = (string) $pdo->query('SELECT @@collation_database')->fetchColumn();
        if (!preg_match('/^utf8mb4_[a-z0-9_]+$/', $collation)) {
            $collation = 'utf8mb4_unicode_ci';
            $pdo->exec('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        $stmt = $pdo->prepare(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
               AND (table_collation IS NULL OR table_collation <> ?)"
        );
        $stmt->execute([$collation]);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
                $pdo->exec('ALTER TABLE `' . str_replace('`', '', $table) . "` CONVERT TO CHARACTER SET utf8mb4 COLLATE {$collation}");
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    },
    'down' => static function (PDO $pdo): void {
        // Nothing to undo — the collations simply stay unified.
    },
];
