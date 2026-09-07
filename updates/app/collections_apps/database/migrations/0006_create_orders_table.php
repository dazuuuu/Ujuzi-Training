<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_ref VARCHAR(30) NOT NULL UNIQUE,
        customer_id INT UNSIGNED NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        discount DECIMAL(10,2) NOT NULL DEFAULT 0,
        shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        currency VARCHAR(5) NOT NULL DEFAULT 'KSH',
        payment_method VARCHAR(100) DEFAULT NULL,
        status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        shipping_address VARCHAR(255) DEFAULT NULL,
        shipping_city VARCHAR(100) DEFAULT NULL,
        shipping_postal_code VARCHAR(20) DEFAULT NULL,
        shipping_country VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS orders',
];
