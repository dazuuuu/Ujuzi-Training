<?php

return [
    'up' => static function (PDO $pdo): void {
        // The tutor sets the final exam's time limit (minutes; NULL = no limit)
        // and its pass mark. The pass mark used to be forced to 100% — those
        // courses go to the new default of 60%, and the tutor can change it.
        $pdo->exec('ALTER TABLE courses ADD COLUMN final_duration_minutes INT UNSIGNED NULL');
        $pdo->exec('UPDATE courses SET final_pass_percent = 60 WHERE final_pass_percent = 100');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE courses DROP COLUMN final_duration_minutes');
    },
];
