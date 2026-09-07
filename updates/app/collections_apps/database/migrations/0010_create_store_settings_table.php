<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS store_settings (
        setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
        setting_value TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS store_settings',
];
