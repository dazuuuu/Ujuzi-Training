<?php
return [
    'up' => 'ALTER TABLE admins ADD COLUMN name VARCHAR(120) DEFAULT NULL AFTER email',
    'down' => 'ALTER TABLE admins DROP COLUMN name',
];
