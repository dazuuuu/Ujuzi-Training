<?php

return [
    'up' => static function (PDO $pdo): void {
        // One ledger for everything. deposit = student tops up their wallet,
        // course_payment = the same student paying a course (debit),
        // earning = the tutor/organisation being credited for that payment.
        $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('deposit','course_payment','earning') NOT NULL,
            amount_ksh DECIMAL(12,2) NOT NULL,
            course_id INT UNSIGNED NULL,
            organisation_id INT UNSIGNED NULL,
            payer_user_id INT UNSIGNED NULL,
            provider VARCHAR(30) NOT NULL DEFAULT 'simulation',
            reference VARCHAR(100) NULL,
            note VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_wallet_user (user_id, type),
            KEY idx_wallet_course (course_id),
            KEY idx_wallet_org (organisation_id, type),
            CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS wallet_transactions');
    },
];
