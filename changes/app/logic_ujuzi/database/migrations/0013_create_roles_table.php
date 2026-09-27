<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS roles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        description TEXT,
        has_admin_features TINYINT(1) NOT NULL DEFAULT 0,
        is_under_organisation TINYINT(1) NOT NULL DEFAULT 1,
        can_manage_users TINYINT(1) NOT NULL DEFAULT 0,
        managed_role_slugs JSON NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS roles',
];
