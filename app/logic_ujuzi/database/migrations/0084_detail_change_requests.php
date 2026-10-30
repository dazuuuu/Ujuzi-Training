<?php

return [
    'title' => 'Account security: one account per person, and approved detail changes',
    'notes' => [
        'No two accounts can share the same full name (first, last and other names all matching), email address, phone number, or National ID / passport / birth certificate number. Anyone trying to register again is told an account already exists in the system.',
        'Once saved, names, sign-in email and ID number are locked. People correct them from Profile → Request a change; the request appears under Detail change requests in your menu, and the account changes only when you approve it.',
        'An account can be signed in on one device at a time: signing in on a new device signs the other device out.',
    ],
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_change_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            changes JSON NOT NULL,
            reason VARCHAR(500) NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            admin_note VARCHAR(500) NULL,
            decided_by INT UNSIGNED NULL,
            decided_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_change_requests_status (status, created_at),
            KEY idx_change_requests_user (user_id, status),
            CONSTRAINT fk_change_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS user_change_requests');
    },
];
