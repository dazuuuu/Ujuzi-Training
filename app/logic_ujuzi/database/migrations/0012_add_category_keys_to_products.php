<?php
return [
    'up' => "ALTER TABLE products
        ADD COLUMN category_keys JSON DEFAULT NULL AFTER category_key",
    'down' => 'ALTER TABLE products DROP COLUMN category_keys',
];
