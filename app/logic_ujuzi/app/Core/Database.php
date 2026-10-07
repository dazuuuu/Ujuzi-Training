<?php

namespace App\Core;

use PDO;

/**
 * The installation's settings and the database connection — the one file to
 * edit on each host.
 */
class Database
{
    public const SETTINGS = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'ujuzi_training',
        'DB_USER' => 'root',
        'DB_PASS' => '',

        // Exact URL the public folder is served at, no trailing slash, e.g.
        // https://example.com or https://dazuaihub.com/Ujuzi/public. Blank = work it out from the request.
        'APP_URL' => '',
        'APP_NAME' => 'Ujuzi Training',
        'APP_DEBUG' => '1',
        'APP_TIMEZONE' => 'Africa/Nairobi',

        // Only used the first time the database is seeded from the command line.
        'ADMIN_SEED_EMAIL' => 'admin@example.com',
        'ADMIN_SEED_PASSWORD' => 'change-this-password',
    ];

    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Env::get('DB_HOST'), Env::get('DB_PORT'), Env::get('DB_NAME'));
            self::$connection = new PDO($dsn, (string) Env::get('DB_USER'), (string) Env::get('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$connection;
    }
}
