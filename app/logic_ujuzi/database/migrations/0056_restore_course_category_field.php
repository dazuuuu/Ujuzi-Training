<?php

return [
    'up' => static function (PDO $pdo): void {
        // Categories belong to one organisation and are what a tutor's course
        // actually falls under (created by the organisation admin via
        // Categories). Which organisation the course belongs to is resolved
        // automatically from the tutor's own approved organisation — the
        // category is scoped to that organisation only.
        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Create a course' LIMIT 1")->fetchColumn();
        if (!$formId) {
            return;
        }
        $exists = $pdo->prepare('SELECT id FROM form_fields WHERE form_id = ? AND field_key = ? LIMIT 1');
        $exists->execute([(int) $formId, 'course_category']);
        if ($exists->fetchColumn()) {
            return;
        }
        $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $formId,
            'Category',
            'course_category',
            'category',
            null,
            null,
            'Choose the category this course falls under, from your organisation\'s categories.',
            1,
            1,
        ]);
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'course_category' AND field_type = 'category'");
    },
];
