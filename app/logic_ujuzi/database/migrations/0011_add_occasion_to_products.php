<?php
return [
    'up' => "ALTER TABLE products
        ADD COLUMN occasion VARCHAR(80) DEFAULT NULL AFTER sub_category",
    'down' => 'ALTER TABLE products DROP COLUMN occasion',
];
