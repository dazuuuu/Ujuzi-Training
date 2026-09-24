<?php

namespace App\Core;

/**
 * Loads .env (via vlucas/phpdotenv) once per request from the private
 * application bundle, and exposes a global env() helper.
 */
class Env
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        $root = dirname(__DIR__, 2);
        if (file_exists($root . '/.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($root);
            $dotenv->safeLoad();
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}
