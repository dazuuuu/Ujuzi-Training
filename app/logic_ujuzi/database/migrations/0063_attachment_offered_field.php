<?php

return [
    'up' => static function (PDO $pdo): void {
        // Attachment providers list what kind of attachment they offer, so
        // students can browse and apply (like job listings).
        $formId = $pdo->query("SELECT id FROM forms WHERE title = 'Organisation providing Attachments' LIMIT 1")->fetchColumn();
        if (!$formId) {
            return;
        }
        $exists = $pdo->prepare('SELECT id FROM form_fields WHERE form_id = ? AND field_key = ? LIMIT 1');
        $exists->execute([(int) $formId, 'attachment_offered']);
        if ($exists->fetchColumn()) {
            return;
        }
        $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $formId,
            'Kind of attachment you offer',
            'attachment_offered',
            'paragraph',
            null,
            'e.g. Industrial attachment in electrical engineering, 3 months',
            'Describe the attachment opportunities students can apply for. Students see this when choosing where to go.',
            1,
            4,
        ]);
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM form_fields WHERE field_key = 'attachment_offered'");
    },
];
