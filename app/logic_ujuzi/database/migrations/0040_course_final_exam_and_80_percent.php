<?php

return [
    'up' => static function (PDO $pdo): void {
        try {
            $pdo->exec('ALTER TABLE course_modules MODIFY pass_percent TINYINT UNSIGNED NOT NULL DEFAULT 80');
        } catch (Throwable $e) {
            // Older engines or already-correct schemas can safely continue.
        }

        try {
            $pdo->exec('UPDATE course_modules SET pass_percent = 80 WHERE pass_percent < 80');
        } catch (Throwable $e) {
            // Table may not exist yet on partial installs.
        }

        try {
            $pdo->exec('ALTER TABLE courses ADD COLUMN final_exam_questions JSON NULL AFTER answers');
        } catch (Throwable $e) {
            // Column already exists.
        }

        try {
            $pdo->exec('ALTER TABLE courses ADD COLUMN final_pass_percent TINYINT UNSIGNED NOT NULL DEFAULT 80 AFTER final_exam_questions');
        } catch (Throwable $e) {
            // Column already exists.
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS course_final_exam_progress (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            score TINYINT UNSIGNED NOT NULL DEFAULT 0,
            passed TINYINT(1) NOT NULL DEFAULT 0,
            answers JSON NULL,
            completed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_course_final_exam (user_id, course_id),
            KEY idx_final_exam_course (course_id, user_id),
            CONSTRAINT fk_final_exam_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_final_exam_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => 'DROP TABLE IF EXISTS course_final_exam_progress;',
];
