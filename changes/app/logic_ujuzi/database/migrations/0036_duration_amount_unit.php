<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "UPDATE form_fields
             SET help_text = 'Enter how long the attachment lasts, such as 1 week, 2 weeks, 1 month, or 1 year.'
             WHERE field_type = 'duration'"
        );
    },
    'down' => static function (PDO $pdo): void {
    },
];
