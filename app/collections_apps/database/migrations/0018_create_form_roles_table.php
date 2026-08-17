<?php
return [
    'up' => "CREATE TABLE IF NOT EXISTS form_roles (
        form_id INT UNSIGNED NOT NULL,
        role_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (form_id, role_id),
        CONSTRAINT fk_form_roles_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
        CONSTRAINT fk_form_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS form_roles',
];
