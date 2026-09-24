<?php

return [
    'up' => static function (PDO $pdo): void {
        $exists = $pdo->query("SELECT id FROM roles WHERE slug = 'course_branch_admin' LIMIT 1")->fetchColumn();
        if ($exists) {
            return;
        }
        $pdo->exec(
            "INSERT INTO roles (slug, name, description, has_admin_features, is_under_organisation, can_manage_users, managed_role_slugs, sort_order)
             VALUES ('course_branch_admin', 'Branch Admin (Courses)', 'Approves students at one branch of an organisation providing courses, and manages that organisation''s course categories.', 1, 1, 0, '[]', 25)"
        );
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM roles WHERE slug = 'course_branch_admin'");
    },
];
