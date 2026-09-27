<?php

return [
    'up' => static function (PDO $pdo): void {
        // Where an attachment provider appears: one row per course category it
        // targets. Students only see providers targeting a category they are
        // approved for at that organisation.
        $pdo->exec("CREATE TABLE IF NOT EXISTS attachment_provider_audiences (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider_user_id INT UNSIGNED NOT NULL,
            organisation_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_audience_provider_category (provider_user_id, category_id),
            KEY idx_audience_category (category_id),
            CONSTRAINT fk_audience_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_audience_organisation FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
            CONSTRAINT fk_audience_category FOREIGN KEY (category_id) REFERENCES organisation_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // A student gets one attachment per course category. Older requests
        // keep a NULL category, which the unique key allows any number of.
        $pdo->exec('ALTER TABLE attachment_applications ADD COLUMN category_id INT UNSIGNED NULL AFTER course_id');
        $pdo->exec('ALTER TABLE attachment_applications ADD UNIQUE KEY uniq_attachment_student_category (student_user_id, category_id)');
        $pdo->exec('ALTER TABLE attachment_applications ADD CONSTRAINT fk_attachment_category FOREIGN KEY (category_id) REFERENCES organisation_categories(id) ON DELETE SET NULL');
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE attachment_applications DROP FOREIGN KEY fk_attachment_category');
        $pdo->exec('ALTER TABLE attachment_applications DROP INDEX uniq_attachment_student_category');
        $pdo->exec('ALTER TABLE attachment_applications DROP COLUMN category_id');
        $pdo->exec('DROP TABLE IF EXISTS attachment_provider_audiences');
    },
];
