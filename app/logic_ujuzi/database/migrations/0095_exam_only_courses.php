<?php

/** Exam-only courses: a body (e.g. NTSA, NITA) administers just an exam — no lessons — and gets a pass/fail report. */
return [
    'title' => 'Exams-only courses',
    'up' => static function (PDO $pdo): void {
        $has = $pdo->query("SHOW COLUMNS FROM courses LIKE 'exam_only'")->fetch();
        if (!$has) {
            $pdo->exec('ALTER TABLE courses ADD exam_only TINYINT(1) NOT NULL DEFAULT 0');
        }
    },
    'down' => static function (PDO $pdo): void {
    },
];
