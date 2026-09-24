<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->prepare("UPDATE roles SET name = 'Attachment Provider' WHERE slug = 'attachment_trainer'")->execute();
        $pdo->prepare("UPDATE roles SET name = 'Tutor / Trainer / Teacher' WHERE slug = 'trainer'")->execute();
    },
    'down' => static function (PDO $pdo): void {
        $pdo->prepare("UPDATE roles SET name = 'Attachment Trainer' WHERE slug = 'attachment_trainer'")->execute();
        $pdo->prepare("UPDATE roles SET name = 'Trainer / Tutor / Teacher' WHERE slug = 'trainer'")->execute();
    },
];
