<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED DEFAULT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_image VARCHAR(500) DEFAULT NULL,
        color_name VARCHAR(100) DEFAULT NULL,
        size_label VARCHAR(100) DEFAULT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS order_items',
];
