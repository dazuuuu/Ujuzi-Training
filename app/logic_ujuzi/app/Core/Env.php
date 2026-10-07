<?php

namespace App\Core;

/**
 * Reads a setting from Database::SETTINGS. A value set in the process
 * environment (e.g. by a test runner) takes precedence.
 */
class Env
{
    public static function load(): void
    {
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = Database::SETTINGS[$key] ?? null;
        }
        return $value === null || $value === '' ? $default : $value;
    }
}
