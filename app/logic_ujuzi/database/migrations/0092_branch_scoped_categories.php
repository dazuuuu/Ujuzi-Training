<?php

/** Categories can belong to one branch (e.g. DCI) so other branches (APS, KPS) never see or assign them. */
return [
    'title' => 'Branch-scoped categories',
    'up' => static function (PDO $pdo): void {
        $has = $pdo->query("SHOW COLUMNS FROM organisation_categories LIKE 'branch_id'")->fetch();
        if (!$has) {
            $pdo->exec('ALTER TABLE organisation_categories ADD branch_id INT UNSIGNED NULL AFTER organisation_id, ADD KEY idx_cat_branch (branch_id)');
        }
    },
    'down' => static function (PDO $pdo): void {
    },
];
