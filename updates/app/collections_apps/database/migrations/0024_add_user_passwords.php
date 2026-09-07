<?php
return [
    'up' => "ALTER TABLE users
        ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER email,
        ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER profile_created_at",
    'down' => "ALTER TABLE users
        DROP COLUMN last_login_at,
        DROP COLUMN password_hash",
];
