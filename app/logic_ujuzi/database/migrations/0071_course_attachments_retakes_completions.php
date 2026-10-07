<?php

return [
    'up' => static function (PDO $pdo): void {
        // Attachment is per course again: a student taking two courses makes
        // one request for each. The course's category is still stored (it is
        // what the provider chose to appear under). declined_provider_ids
        // remembers who already declined this course's request, so the student
        // can't send it to the same organisation twice.
        $pdo->exec('ALTER TABLE attachment_applications DROP INDEX uniq_attachment_student_category');
        $pdo->exec('ALTER TABLE attachment_applications ADD COLUMN declined_provider_ids VARCHAR(255) NULL AFTER provider_note');

        // Earlier requests were filed per category; give each one the
        // student's enrolled course in that category.
        $rows = $pdo->query(
            'SELECT a.id, a.student_user_id, a.category_id FROM attachment_applications a
             WHERE a.course_id IS NULL AND a.category_id IS NOT NULL ORDER BY a.id'
        )->fetchAll(PDO::FETCH_ASSOC);
        $findCourse = $pdo->prepare(
            'SELECT e.course_id FROM course_enrollments e
             INNER JOIN course_categories cc ON cc.course_id = e.course_id
             WHERE e.user_id = ? AND cc.category_id = ?
               AND NOT EXISTS (SELECT 1 FROM attachment_applications x WHERE x.student_user_id = e.user_id AND x.course_id = e.course_id)
             ORDER BY e.enrolled_at ASC LIMIT 1'
        );
        $setCourse = $pdo->prepare('UPDATE attachment_applications SET course_id = ? WHERE id = ?');
        foreach ($rows as $row) {
            $findCourse->execute([(int) $row['student_user_id'], (int) $row['category_id']]);
            $courseId = $findCourse->fetchColumn();
            if ($courseId) {
                $setCourse->execute([(int) $courseId, (int) $row['id']]);
            }
        }

        // Retakes: six months after enrolling, a course resets and must be
        // paid for again. Payments from the earlier run stay in the ledger
        // (the money was spent) but no longer count towards the new run.
        $pdo->exec('ALTER TABLE wallet_transactions ADD COLUMN archived_at TIMESTAMP NULL DEFAULT NULL');

        // Completed courses keep their certificate even after the course resets.
        $pdo->exec('CREATE TABLE IF NOT EXISTS course_completions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            score INT UNSIGNED NULL,
            completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_course_completion (user_id, course_id),
            CONSTRAINT fk_completion_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_completion_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $pdo->exec("INSERT IGNORE INTO course_completions (user_id, course_id, score, completed_at)
            SELECT f.user_id, f.course_id, f.score, COALESCE(f.completed_at, NOW())
            FROM course_final_exam_progress f
            INNER JOIN course_enrollments e ON e.user_id = f.user_id AND e.course_id = f.course_id
            WHERE f.passed = 1 AND e.payment_status = 'paid'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS course_completions');
        $pdo->exec('ALTER TABLE wallet_transactions DROP COLUMN archived_at');
        $pdo->exec('ALTER TABLE attachment_applications DROP COLUMN declined_provider_ids');
        $pdo->exec('ALTER TABLE attachment_applications ADD UNIQUE KEY uniq_attachment_student_category (student_user_id, category_id)');
    },
];
