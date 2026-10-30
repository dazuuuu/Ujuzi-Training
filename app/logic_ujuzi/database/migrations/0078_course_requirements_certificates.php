<?php

return [
    'up' => static function (PDO $pdo): void {
        // What a student needs before taking the course, listed by the tutor (one per line).
        $pdo->exec('ALTER TABLE courses ADD COLUMN requirements TEXT NULL');
        // Certificates Super Admin generates for anyone (registered or not), in batches.
        $pdo->exec("CREATE TABLE IF NOT EXISTS issued_certificates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            certificate_no VARCHAR(40) NOT NULL UNIQUE,
            batch_id VARCHAR(20) NOT NULL,
            recipient_name VARCHAR(190) NOT NULL,
            id_number VARCHAR(80) NULL,
            skills VARCHAR(500) NOT NULL,
            organisation_id INT UNSIGNED NULL,
            organisation_name VARCHAR(190) NULL,
            issued_on DATE NOT NULL,
            issued_by_admin_id INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_batch (batch_id),
            KEY idx_name (recipient_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS issued_certificates');
        $pdo->exec('ALTER TABLE courses DROP COLUMN requirements');
    },
];
