<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec(
            "UPDATE form_fields
             SET label = 'Create branches',
                 help_text = 'Add every branch. Name and location are required on each row.'
             WHERE field_type = 'branches'"
        );
    },
    'down' => static function (PDO $pdo): void {
    },
];
