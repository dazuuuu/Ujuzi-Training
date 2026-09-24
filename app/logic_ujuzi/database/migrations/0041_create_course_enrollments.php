<?php

return [
    'up' => "CREATE TABLE IF NOT EXISTS course_enrollments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        course_id INT UNSIGNED NOT NULL,
        amount_ksh DECIMAL(10,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) NOT NULL DEFAULT 'KSH',
        payment_provider VARCHAR(30) NOT NULL DEFAULT 'manual',
        payment_status VARCHAR(30) NOT NULL DEFAULT 'paid',
        payment_reference VARCHAR(100) NULL,
        enrolled_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_course_enrollment (user_id, course_id),
        KEY idx_course_enrollments_course (course_id, user_id),
        CONSTRAINT fk_course_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_course_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS course_enrollments',
];
