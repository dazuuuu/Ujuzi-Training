<?php
return [
    'up' => "ALTER TABLE form_fields
        MODIFY field_type VARCHAR(40) NOT NULL DEFAULT 'text',
        ADD COLUMN placeholder VARCHAR(191) DEFAULT NULL AFTER options,
        ADD COLUMN help_text TEXT DEFAULT NULL AFTER placeholder",
    'down' => "ALTER TABLE form_fields
        DROP COLUMN placeholder,
        DROP COLUMN help_text,
        MODIFY field_type ENUM('text','paragraph','dropdown','number','date','datetime') NOT NULL DEFAULT 'text'",
];
