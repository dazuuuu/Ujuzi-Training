<?php

/** "Student lookup" is now called "Verify Certificate" on saved menus and pages. */
return [
    'title' => 'Rename Student lookup to Verify Certificate',
    'up' => static function (PDO $pdo): void {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_value LIKE '%Student lookup%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $save = $pdo->prepare('UPDATE store_settings SET setting_value = ? WHERE setting_key = ?');
        foreach ($rows as $key => $value) {
            $save->execute([str_replace('Student lookup', 'Verify Certificate', (string) $value), $key]);
        }
    },
    'down' => static function (PDO $pdo): void {
    },
];
