<?php

return [
    'up' => static function (PDO $pdo): void {
        // Final exam: the tutor's question bank; each student sits a random
        // paper of this many questions (NULL = every question, shuffled).
        $pdo->exec('ALTER TABLE courses ADD COLUMN final_paper_size INT UNSIGNED NULL');
        // The certificate needs a perfect final exam.
        $pdo->exec('UPDATE courses SET final_pass_percent = 100');

        // The attachment organisation's assessment of a student, marked against
        // its criteria and shared with the organisation that runs the course.
        $pdo->exec("CREATE TABLE IF NOT EXISTS attachment_assessments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            items JSON NOT NULL,
            total_score DECIMAL(8,2) NOT NULL DEFAULT 0,
            max_score DECIMAL(8,2) NOT NULL DEFAULT 0,
            percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            remarks TEXT NULL,
            assessed_by_user_id INT UNSIGNED NULL,
            assessed_at TIMESTAMP NULL DEFAULT NULL,
            shared_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_assessment_application (application_id),
            CONSTRAINT fk_assessment_application FOREIGN KEY (application_id) REFERENCES attachment_applications(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS attachment_assessments');
        $pdo->exec('ALTER TABLE courses DROP COLUMN final_paper_size');
    },
];
