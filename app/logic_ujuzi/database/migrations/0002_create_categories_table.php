<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_key VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        tagline VARCHAR(255) DEFAULT NULL,
        image VARCHAR(500) DEFAULT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS categories',
];
