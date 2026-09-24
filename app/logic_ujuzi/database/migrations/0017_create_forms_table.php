<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS forms (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(191) NOT NULL,
        description TEXT,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by_admin_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_forms_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS forms',
];
