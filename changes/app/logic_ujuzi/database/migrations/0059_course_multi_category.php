<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS course_categories (
                course_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (course_id, category_id),
                CONSTRAINT fk_course_categories_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                CONSTRAINT fk_course_categories_category FOREIGN KEY (category_id) REFERENCES organisation_categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        // Backfill: every existing course's single category becomes its first pivot row.
        $pdo->exec(
            'INSERT IGNORE INTO course_categories (course_id, category_id)
             SELECT id, category_id FROM courses WHERE category_id IS NOT NULL'
        );
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS course_categories');
    },
];
