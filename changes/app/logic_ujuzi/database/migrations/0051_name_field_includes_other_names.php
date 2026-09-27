<?php

return [
    'up' => static function (PDO $pdo): void {
        // The "name" field type now asks for first, last, AND other names in
        // one widget, so the separate standalone "Other names" text field on
        // the seeded Tutor/Student forms is redundant.
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'other_names'");
    },
    'down' => static function (PDO $pdo): void {
        $insert = $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($pdo->query("SELECT id FROM forms WHERE title IN ('Tutor registration', 'Student registration')")->fetchAll(PDO::FETCH_COLUMN) as $formId) {
            $insert->execute([
                (int) $formId,
                'Other names',
                'other_names',
                'text',
                null,
                'Optional',
                'Any middle or additional names not covered above.',
                0,
                1,
            ]);
        }
    },
];
