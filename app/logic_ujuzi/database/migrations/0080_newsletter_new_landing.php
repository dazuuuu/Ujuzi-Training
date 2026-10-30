<?php

return [
    'up' => static function (PDO $pdo): void {
        // People who subscribe from the footer of the public site.
        $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // The new landing page design: the homepage goes back to its (new)
        // default layout. The old layout is kept so it can be restored.
        $stmt = $pdo->prepare("SELECT setting_value FROM store_settings WHERE setting_key = 'page_layout_home'");
        $stmt->execute();
        $old = (string) $stmt->fetchColumn();
        if ($old !== '') {
            $pdo->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES ('page_layout_home_before_landing', ?)
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$old]);
            $pdo->exec("UPDATE store_settings SET setting_value = '' WHERE setting_key = 'page_layout_home'");
        }
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec("UPDATE store_settings s INNER JOIN store_settings b ON b.setting_key = 'page_layout_home_before_landing'
                    SET s.setting_value = b.setting_value WHERE s.setting_key = 'page_layout_home'");
        $pdo->exec('DROP TABLE IF EXISTS newsletter_subscribers');
    },
];
