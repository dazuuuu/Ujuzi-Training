<?php

return [
    'up' => static function (PDO $pdo): void {
        $exists = $pdo->query("SELECT id FROM roles WHERE slug = 'branch_admin' LIMIT 1")->fetchColumn();
        if (!$exists) {
            $pdo->exec(
                "INSERT INTO roles (slug, name, description, has_admin_features, is_under_organisation, can_manage_users, managed_role_slugs, sort_order)
                 VALUES ('branch_admin', 'Branch Admin', 'Manages attachees at a single branch: accepts and marks their attachment complete. Assigned by an organisation admin or attachment provider.', 0, 1, 0, '[]', 21)"
            );
        }

        foreach (['organisation_admin', 'attachment_trainer'] as $slug) {
            $row = $pdo->prepare('SELECT managed_role_slugs FROM roles WHERE slug = ? LIMIT 1');
            $row->execute([$slug]);
            $current = $row->fetchColumn();
            $list = $current ? (json_decode((string) $current, true) ?: []) : [];
            if (!is_array($list)) {
                $list = [];
            }
            if (!in_array('branch_admin', $list, true)) {
                $list[] = 'branch_admin';
            }
            $pdo->prepare('UPDATE roles SET managed_role_slugs = ? WHERE slug = ?')
                ->execute([json_encode(array_values($list)), $slug]);
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM roles WHERE slug = 'branch_admin'");
    },
];
