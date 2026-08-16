<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS form_responses (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        form_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        answers JSON NULL,
        submitted_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_form_user (form_id, user_id),
        CONSTRAINT fk_form_responses_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
        CONSTRAINT fk_form_responses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS form_responses',
];
