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
                $table = str_replace('`', '', $table);
                try {
                    $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE {$collation}");
                } catch (\Throwable $e) {
                    // e.g. 1832 "Cannot change column ... used in a foreign key constraint":
                    // one table must never stop the whole update. Keep going; at least new
                    // columns in that table get the right collation.
                    error_log("Collation unify skipped {$table}: " . $e->getMessage());
                    try {
                        $pdo->exec("ALTER TABLE `{$table}` DEFAULT CHARACTER SET utf8mb4 COLLATE {$collation}");
                    } catch (\Throwable $ignored) {
                    }
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    },
    'down' => static function (PDO $pdo): void {
        // Nothing to undo — the collations simply stay unified.
    },
];
