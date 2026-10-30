<?php

return [
    'up' => static function (PDO $pdo): void {
        // Every earned certificate gets its own number (one per student per course), used to verify it.
        $pdo->exec("CREATE TABLE IF NOT EXISTS earned_certificates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            certificate_no VARCHAR(40) NOT NULL UNIQUE,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_course (user_id, course_id),
            KEY idx_course (course_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // The branch a course is offered by (its tutor's branch); NULL = the organisation itself.
        $pdo->exec('ALTER TABLE courses ADD COLUMN branch_id INT UNSIGNED NULL AFTER organisation_id, ADD KEY idx_course_branch (branch_id)');
        // Courses already made by tutors who teach at a branch belong to that branch.
        $pdo->exec("UPDATE courses c
                    INNER JOIN organisation_memberships m ON m.user_id = c.trainer_user_id AND m.organisation_id = c.organisation_id AND m.status = 'approved'
                    SET c.branch_id = m.branch_id
                    WHERE c.branch_id IS NULL AND m.branch_id IS NOT NULL");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE courses DROP KEY idx_course_branch, DROP COLUMN branch_id');
        $pdo->exec('DROP TABLE IF EXISTS earned_certificates');
    },
];
