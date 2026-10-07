<?php

return [
    'up' => static function (PDO $pdo): void {
        // What students see about a tutor: photo, contacts and background.
        $pdo->exec('ALTER TABLE users
            ADD COLUMN photo_path VARCHAR(255) NULL,
            ADD COLUMN headline VARCHAR(160) NULL,
            ADD COLUMN bio TEXT NULL,
            ADD COLUMN experience TEXT NULL,
            ADD COLUMN linkedin_url VARCHAR(255) NULL,
            ADD COLUMN social_url VARCHAR(255) NULL');

        // What students see about an organisation.
        $pdo->exec('ALTER TABLE organisations
            ADD COLUMN phone VARCHAR(40) NULL,
            ADD COLUMN email VARCHAR(190) NULL,
            ADD COLUMN location VARCHAR(190) NULL,
            ADD COLUMN website VARCHAR(255) NULL,
            ADD COLUMN logo_path VARCHAR(255) NULL');

        // An organisation providing attachment now asks each organisation
        // providing courses to take its students; it appears to them once
        // approved. Providers already showing keep showing.
        $pdo->exec("ALTER TABLE attachment_provider_audiences
            ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            ADD COLUMN reviewed_by_user_id INT UNSIGNED NULL,
            ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL");
        $pdo->exec("UPDATE attachment_provider_audiences SET status = 'approved', reviewed_at = NOW()");

        $pdo->exec("ALTER TABLE user_otp_codes MODIFY purpose ENUM('login','password_reset','password_change') NOT NULL DEFAULT 'login'");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("DELETE FROM user_otp_codes WHERE purpose = 'password_change'");
        $pdo->exec("ALTER TABLE user_otp_codes MODIFY purpose ENUM('login','password_reset') NOT NULL DEFAULT 'login'");
        $pdo->exec('ALTER TABLE attachment_provider_audiences DROP COLUMN status, DROP COLUMN reviewed_by_user_id, DROP COLUMN reviewed_at');
        $pdo->exec('ALTER TABLE organisations DROP COLUMN phone, DROP COLUMN email, DROP COLUMN location, DROP COLUMN website, DROP COLUMN logo_path');
        $pdo->exec('ALTER TABLE users DROP COLUMN photo_path, DROP COLUMN headline, DROP COLUMN bio, DROP COLUMN experience, DROP COLUMN linkedin_url, DROP COLUMN social_url');
    },
];
