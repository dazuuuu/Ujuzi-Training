<?php

return [
    'up' => static function (PDO $pdo): void {
        // Super Admins can add other admins with limited access. An "owner"
        // has everything and manages admins; others get only the sections in
        // `permissions` (a JSON list). Everyone who exists today is an owner.
        $pdo->exec('ALTER TABLE admins ADD COLUMN is_owner TINYINT(1) NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE admins ADD COLUMN permissions JSON NULL');
        $pdo->exec('ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
        $pdo->exec('ALTER TABLE admins ADD COLUMN created_by_admin_id INT UNSIGNED NULL');
        $pdo->exec('UPDATE admins SET is_owner = 1');

        // Courses Super Admin picks to show on the public homepage.
        $pdo->exec('ALTER TABLE courses ADD COLUMN featured_on_home TINYINT(1) NOT NULL DEFAULT 0');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE courses DROP COLUMN featured_on_home');
        $pdo->exec('ALTER TABLE admins DROP COLUMN created_by_admin_id');
        $pdo->exec('ALTER TABLE admins DROP COLUMN is_active');
        $pdo->exec('ALTER TABLE admins DROP COLUMN permissions');
        $pdo->exec('ALTER TABLE admins DROP COLUMN is_owner');
    },
];
