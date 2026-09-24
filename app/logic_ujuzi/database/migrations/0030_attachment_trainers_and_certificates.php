<?php

return [
    'up' => static function (PDO $pdo): void {
        $pdo->prepare(
            "UPDATE roles
             SET description = ?, has_admin_features = 0, can_manage_users = 0, managed_role_slugs = '[]'
             WHERE slug = 'attachment_trainer'"
        )->execute([
            'Hosts workplace attachment after a student finishes their course. Profile form includes a reusable attachment duration. Students only see attachment trainers once they have completed a course.',
        ]);

        $exists = $pdo->query("SELECT id FROM roles WHERE slug = 'attachment_trainer' LIMIT 1")->fetchColumn();
        if (!$exists) {
            $pdo->exec(
                "INSERT INTO roles (slug, name, description, has_admin_features, is_under_organisation, can_manage_users, managed_role_slugs, sort_order)
                 VALUES ('attachment_trainer', 'Attachment Trainer', 'Hosts workplace attachment after a student finishes their course. Profile form includes a reusable attachment duration.', 0, 1, 0, '[]', 20)"
            );
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec(
            "UPDATE roles SET description = 'Has admin-like tools for students in their organisation. Works under the organisation admin.' WHERE slug = 'attachment_trainer'"
        );
    },
];
