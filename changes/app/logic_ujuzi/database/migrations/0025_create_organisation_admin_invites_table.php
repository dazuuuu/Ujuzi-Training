<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS organisation_admin_invites (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        organisation_id INT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL DEFAULT NULL,
        used_by_user_id INT UNSIGNED NULL DEFAULT NULL,
        created_by_admin_id INT UNSIGNED NOT NULL,
        emailed_to VARCHAR(255) NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_oai_token_hash (token_hash),
        KEY idx_oai_org (organisation_id),
        CONSTRAINT fk_oai_organisation FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
        CONSTRAINT fk_oai_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        CONSTRAINT fk_oai_user FOREIGN KEY (used_by_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS organisation_admin_invites',
];
