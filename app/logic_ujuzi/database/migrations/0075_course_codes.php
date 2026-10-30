<?php

return [
    'up' => static function (PDO $pdo): void {
        // Super Admin gives each organisation a range of course codes (e.g.
        // 101–130); each of its courses takes one, so students can find a
        // course by typing its code. Codes are never printed on certificates.
        $pdo->exec('ALTER TABLE organisations ADD COLUMN course_code_from INT UNSIGNED NULL, ADD COLUMN course_code_to INT UNSIGNED NULL');
        $pdo->exec('ALTER TABLE courses ADD COLUMN course_code INT UNSIGNED NULL');
        $pdo->exec('ALTER TABLE courses ADD UNIQUE KEY uniq_course_code (course_code)');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE courses DROP INDEX uniq_course_code');
        $pdo->exec('ALTER TABLE courses DROP COLUMN course_code');
        $pdo->exec('ALTER TABLE organisations DROP COLUMN course_code_from, DROP COLUMN course_code_to');
    },
];
