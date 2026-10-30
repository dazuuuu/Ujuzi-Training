<?php

return [
    'up' => static function (PDO $pdo): void {
        // Gender is one choice: checkboxes become radio buttons (saved answers keep their first choice).
        $pdo->exec("UPDATE form_fields SET field_type = 'radio'
                    WHERE field_type IN ('checkboxes', 'multiselect') AND (LOWER(label) LIKE '%gender%' OR LOWER(label) = 'sex')");
        // National ID / passport / birth certificate number: pick one document and its number, always required.
        $pdo->exec("UPDATE form_fields SET field_type = 'id_document', is_required = 1
                    WHERE field_type IN ('text', 'number')
                      AND (LOWER(label) LIKE '%national id%' OR LOWER(label) LIKE '%birth cert%' OR LOWER(label) LIKE '%passport%' OR LOWER(label) LIKE '%id number%' OR LOWER(label) LIKE '%id no%')");
        $pdo->exec("UPDATE form_fields SET label = 'National ID, passport or birth certificate number'
                    WHERE field_type = 'id_document' AND LOWER(label) LIKE '%national id or birth%'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE form_fields SET field_type = 'text' WHERE field_type = 'id_document'");
    },
];
