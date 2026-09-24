<?php

return [
    'up' => static function (PDO $pdo): void {
        foreach ([
            "ALTER TABLE courses ADD COLUMN enrollment_fee_ksh DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER visibility",
            "ALTER TABLE courses ADD COLUMN introduction_title VARCHAR(191) NULL AFTER enrollment_fee_ksh",
            "ALTER TABLE courses ADD COLUMN introduction_description TEXT NULL AFTER introduction_title",
            "ALTER TABLE courses ADD COLUMN introduction_video_source ENUM('upload','youtube') NOT NULL DEFAULT 'youtube' AFTER introduction_description",
            "ALTER TABLE courses ADD COLUMN introduction_video_path VARCHAR(500) NULL AFTER introduction_video_source",
            "ALTER TABLE courses ADD COLUMN introduction_video_url VARCHAR(500) NULL AFTER introduction_video_path",
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Column already exists.
            }
        }

        foreach ([
            "ALTER TABLE course_enrollments ADD COLUMN amount_ksh DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER course_id",
            "ALTER TABLE course_enrollments ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'KSH' AFTER amount_ksh",
            "ALTER TABLE course_enrollments ADD COLUMN payment_provider VARCHAR(30) NOT NULL DEFAULT 'manual' AFTER currency",
            "ALTER TABLE course_enrollments ADD COLUMN payment_status VARCHAR(30) NOT NULL DEFAULT 'paid' AFTER payment_provider",
            "ALTER TABLE course_enrollments ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status",
        ] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Column already exists.
            }
        }
    },
    'down' => '',
];
