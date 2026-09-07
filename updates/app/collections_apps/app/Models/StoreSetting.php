<?php

namespace App\Models;

use App\Core\Database;

class StoreSetting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = Database::connection()->query('SELECT setting_key, setting_value FROM store_settings')->fetchAll();
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = self::all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::connection()->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
        self::$cache = null;
    }
}
