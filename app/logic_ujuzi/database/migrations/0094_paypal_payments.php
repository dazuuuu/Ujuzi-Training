<?php

/** PayPal orders for buying coins; the wallet is credited only after PayPal confirms the capture. */
return [
    'title' => 'PayPal payments',
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS paypal_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            order_id VARCHAR(40) NOT NULL,
            amount_ksh DECIMAL(12,2) NOT NULL,
            amount_usd DECIMAL(12,2) NOT NULL,
            status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
            capture_id VARCHAR(40) NULL,
            return_to VARCHAR(120) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_paypal_order (order_id),
            KEY idx_paypal_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS paypal_payments');
    },
];
