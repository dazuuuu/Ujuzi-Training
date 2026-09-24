<?php

return [
    'up' => static function (PDO $pdo): void {
        // Tutors create courses under whichever organisation approved them —
        // no separate category to pick before they can save a course. Every
        // course still gets a category behind the scenes (auto-provisioned
        // via OrganisationCategory::defaultForOrganisation), it's just no
        // longer a blocking step in the "Create a course" form.
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'course_category' AND field_type = 'category'");
    },
    'down' => static function (PDO $pdo): void {
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
            'Only categories from organisations that have approved you are listed.',
            1,
            2,
        ]);
    },
];
