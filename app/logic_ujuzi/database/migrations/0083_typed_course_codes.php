<?php

return [
    'title' => 'Reports by organisation, menu editor, help guide and typed course codes',
    'notes' => [
        'Reports → Organisation activity: pick organisations providing courses or attachment, one organisation (or all), a branch (or all branches), a status (completed, ongoing, accepted…), a finance status and dates. Rows show dates and times; download exactly that list as Excel or PDF.',
        'Attachment payments now show each student\'s status and can list Completed, Ongoing, or both.',
        'Navigation: rename and reorder the menu of every portal (students, tutors, both kinds of organisation and branch admin), your own menu, and the public website menu.',
        'System guide & help (in your menu) explains how everything works and where; type a question to get the answer.',
        'Cards across the portals are smaller and tighter.',
        'Tutors now type a course code (e.g. CS101 or ICT-204) when creating or editing a course. Codes may contain letters, numbers and dashes and must be unique in the whole system.',
        'Codes from an organisation\'s range (Organisations → Course codes) are still offered as suggestions.',
    ],
    'up' => static function (PDO $pdo): void {
        // Course codes become text so tutors can type codes such as CS101.
        $pdo->exec('ALTER TABLE courses MODIFY course_code VARCHAR(20) NULL');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE courses SET course_code = NULL WHERE course_code NOT REGEXP '^[0-9]+$'");
        $pdo->exec('ALTER TABLE courses MODIFY course_code INT UNSIGNED NULL');
    },
];
