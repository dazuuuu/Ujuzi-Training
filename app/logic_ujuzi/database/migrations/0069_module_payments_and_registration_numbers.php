<?php

return [
    'up' => static function (PDO $pdo): void {
        // Paying per module: one row each time a student opens (pays for) a
        // module. price_ksh is the module's share of the course fee;
        // from_wallet_ksh is what was deducted from the wallet (the rest came
        // from money already paid towards the course). expires_at ends the
        // month of access, after which a passed module moves to History.
        $pdo->exec("CREATE TABLE IF NOT EXISTS course_module_access (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            module_id INT UNSIGNED NOT NULL,
            price_ksh DECIMAL(12,2) NOT NULL DEFAULT 0,
            from_wallet_ksh DECIMAL(12,2) NOT NULL DEFAULT 0,
            unlocked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            UNIQUE KEY uniq_module_access (user_id, module_id),
            KEY idx_module_access_course (user_id, course_id),
            CONSTRAINT fk_module_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_module_access_module FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Student registration numbers, e.g. UJ0012709/26: UJ, the student's
        // place in the order students registered (001, 002 …), then the day
        // and month they registered, then /year.
        $pdo->exec('ALTER TABLE users ADD COLUMN registration_seq INT UNSIGNED NULL');
        $pdo->exec('ALTER TABLE users ADD COLUMN registration_number VARCHAR(32) NULL');
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_registration_seq (registration_seq)');
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_registration_number (registration_number)');

        // Number the students who already exist, oldest first.
        $students = $pdo->query(
            "SELECT u.id, u.created_at FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'student' ORDER BY u.created_at ASC, u.id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE users SET registration_seq = ?, registration_number = ? WHERE id = ?');
        foreach ($students as $i => $student) {
            $seq = $i + 1;
            $at = strtotime((string) $student['created_at']) ?: time();
            $number = 'UJ' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . date('d', $at) . date('m', $at) . '/' . date('y', $at);
            $update->execute([$seq, $number, (int) $student['id']]);
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE users DROP INDEX uniq_registration_number');
        $pdo->exec('ALTER TABLE users DROP INDEX uniq_registration_seq');
        $pdo->exec('ALTER TABLE users DROP COLUMN registration_number');
        $pdo->exec('ALTER TABLE users DROP COLUMN registration_seq');
        $pdo->exec('DROP TABLE IF EXISTS course_module_access');
    },
];
