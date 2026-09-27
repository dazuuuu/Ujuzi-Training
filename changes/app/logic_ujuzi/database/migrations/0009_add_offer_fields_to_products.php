<?php
return [
    'up' => "ALTER TABLE products
        ADD COLUMN offer_image VARCHAR(255) DEFAULT NULL AFTER images,
        ADD COLUMN offer_ends_at DATETIME DEFAULT NULL AFTER offer_image",
    'down' => 'ALTER TABLE products DROP COLUMN offer_image, DROP COLUMN offer_ends_at',
];
