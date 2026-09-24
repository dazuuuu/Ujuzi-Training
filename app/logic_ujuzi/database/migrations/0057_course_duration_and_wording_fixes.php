<?php

return [
    'up' => static function (PDO $pdo): void {
        // Repair any misspelled "Course descriptio" label from before this fix existed.
        $pdo->exec("UPDATE form_fields SET label = 'Course description' WHERE field_key = 'course_description' AND label <> 'Course description'");

        // "Student/Learner/Employee" portal naming.
        $pdo->exec("UPDATE roles SET name = 'Student/Learner/Employee' WHERE slug = 'student'");

        // Course duration field on the "Create a course" form.
        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Create a course' LIMIT 1")->fetchColumn();
        if ($formId) {
            $exists = $pdo->prepare('SELECT id FROM form_fields WHERE form_id = ? AND field_key = ? LIMIT 1');
            $exists->execute([(int) $formId, 'course_duration']);
            if (!$exists->fetchColumn()) {
                $pdo->prepare(
                    'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    (int) $formId,
                    'Course duration',
                    'course_duration',
                    'duration',
                    null,
                    null,
                    'How long the course takes to complete.',
                    0,
                    3,
                ]);
            }
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE roles SET name = 'Student' WHERE slug = 'student'");
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'course_duration' AND field_type = 'duration'");
    },
];
