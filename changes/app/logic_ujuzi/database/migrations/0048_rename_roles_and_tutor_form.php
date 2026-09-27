<?php

return [
    'up' => static function (PDO $pdo): void {
        $renames = [
            'organisation_admin' => 'Organisations providing courses',
            'attachment_trainer' => 'Organisation providing Attachment',
            'branch_admin' => 'Attachment providing admin',
            'trainer' => 'Tutor',
        ];
        $stmt = $pdo->prepare('UPDATE roles SET name = ? WHERE slug = ?');
        foreach ($renames as $slug => $name) {
            $stmt->execute([$name, $slug]);
        }

        // In case migration 0047 had already run under the old form title.
        $pdo->prepare("UPDATE forms SET title = 'Tutor registration' WHERE title = 'Trainer / Tutor / Teacher registration'")->execute();
    },
    'down' => static function (PDO $pdo): void {
        $restores = [
            'organisation_admin' => 'Organisation Admin',
            'attachment_trainer' => 'Attachment Provider',
            'branch_admin' => 'Branch Admin',
            'trainer' => 'Tutor / Trainer / Teacher',
        ];
        $stmt = $pdo->prepare('UPDATE roles SET name = ? WHERE slug = ?');
        foreach ($restores as $slug => $name) {
            $stmt->execute([$name, $slug]);
        }
    },
];
