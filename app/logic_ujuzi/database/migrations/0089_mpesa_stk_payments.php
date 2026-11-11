<?php

/** Pending/settled M-Pesa STK push requests; the wallet is credited only when Safaricom's callback confirms. */
return [
    'title' => 'M-Pesa STK push payments',
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS mpesa_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            checkout_request_id VARCHAR(100) NOT NULL,
            merchant_request_id VARCHAR(100) NULL,
            phone VARCHAR(20) NOT NULL,
            amount_ksh DECIMAL(12,2) NOT NULL,
            status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
            mpesa_receipt VARCHAR(40) NULL,
            result_desc VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_mpesa_checkout (checkout_request_id),
            KEY idx_mpesa_user (user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS mpesa_payments');
    },
];
