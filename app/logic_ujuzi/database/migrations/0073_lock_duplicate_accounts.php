<?php

return [
    'up' => static function (PDO $pdo): void {
        // A locked account can only sign in to fix its details; it unlocks
        // once its new email is verified (or Super Admin unlocks it).
        $pdo->exec('ALTER TABLE users
            ADD COLUMN locked_at TIMESTAMP NULL DEFAULT NULL,
            ADD COLUMN lock_reason VARCHAR(255) NULL,
            ADD COLUMN pending_email VARCHAR(255) NULL,
            ADD COLUMN email_verify_token VARCHAR(64) NULL,
            ADD COLUMN email_verify_expires TIMESTAMP NULL DEFAULT NULL');

        // Accounts sharing an email (in any capitals) or a phone number
        // (written any way: 07…, +2547…, 2547…) are all locked until each
        // gives its own details.
        $lock = $pdo->prepare('UPDATE users SET locked_at = NOW(), lock_reason = ? WHERE id = ? AND locked_at IS NULL');

        $emails = $pdo->query(
            "SELECT LOWER(TRIM(email)) AS k, GROUP_CONCAT(id) AS ids FROM users
             WHERE email IS NOT NULL AND TRIM(email) <> ''
             GROUP BY k HAVING COUNT(*) > 1"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($emails as $row) {
            foreach (explode(',', $row['ids']) as $id) {
                $lock->execute(['Another account uses the same email address.', (int) $id]);
            }
        }

        $phones = [];
        foreach ($pdo->query("SELECT id, phone FROM users WHERE phone IS NOT NULL AND phone <> ''")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $digits = preg_replace('/\D/', '', (string) $row['phone']);
            if (strlen($digits) >= 9) {
                $phones[substr($digits, -9)][] = (int) $row['id'];
            }
        }
        foreach ($phones as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $lock->execute(['Another account uses the same phone number.', $id]);
                }
            }
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('ALTER TABLE users DROP COLUMN locked_at, DROP COLUMN lock_reason, DROP COLUMN pending_email,
            DROP COLUMN email_verify_token, DROP COLUMN email_verify_expires');
    },
];
