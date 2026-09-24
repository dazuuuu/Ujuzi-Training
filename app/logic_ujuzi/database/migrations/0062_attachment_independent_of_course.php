<?php

return [
    'up' => static function (PDO $pdo): void {
        // Attachment is open to every student and no longer tied to a course.
        $pdo->exec('ALTER TABLE attachment_applications MODIFY course_id INT UNSIGNED NULL');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DELETE FROM attachment_applications WHERE course_id IS NULL');
        $pdo->exec('ALTER TABLE attachment_applications MODIFY course_id INT UNSIGNED NOT NULL');
    },
];
