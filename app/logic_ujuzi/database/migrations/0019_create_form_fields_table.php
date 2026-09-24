<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS form_fields (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        form_id INT UNSIGNED NOT NULL,
        label VARCHAR(191) NOT NULL,
        field_key VARCHAR(191) NOT NULL,
        field_type ENUM('text','paragraph','dropdown','number','date','datetime') NOT NULL DEFAULT 'text',
        options JSON NULL,
        is_required TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_form_field_key (form_id, field_key),
        CONSTRAINT fk_form_fields_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS form_fields',
];
