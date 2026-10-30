<?php

namespace App\Core;

/**
 * Reads a setting. Order: the process environment (e.g. a test runner), then
 * Database::SETTINGS in app/Core/Database.php — the one settings file, which
 * the /setup page also writes to.
 */
class Env
{
    /** Values saved during this request (the class constant only changes on the next request). */
    private static array $runtime = [];

    public static function load(): void
    {
        // An old installer settings file would hide Database.php's values: retire it.
        $old = dirname(__DIR__, 2) . '/config.local.php';
        if (is_file($old)) {
            @unlink($old);
        }
    }

    /** The settings file. */
    public static function settingsPath(): string
    {
        return __DIR__ . '/Database.php';
    }

    /**
     * Writes $values into Database::SETTINGS in Database.php (adding keys it
     * doesn't have yet). Returns false when the file can't be written; the
     * values are then used for the rest of this browser session only.
     */
    public static function save(array $values): bool
    {
        $values = array_map('strval', $values);
        self::$runtime = array_merge(self::$runtime, $values);
        $path = self::settingsPath();
        $code = (string) @file_get_contents($path);
        foreach ($values as $key => $value) {
            $pattern = "/('" . preg_quote($key, '/') . "'\\s*=>\\s*)'(?:[^'\\\\]|\\\\.)*'/";
            $literal = var_export($value, true);
            if (preg_match($pattern, $code)) {
                $code = preg_replace_callback($pattern, static fn(array $m): string => $m[1] . $literal, $code, 1);
            } else {
                // New key: add it at the end of the SETTINGS array.
                $code = preg_replace_callback(
                    '/(const SETTINGS = \[.*?)(\n\s*\];)/s',
                    static fn(array $m): string => $m[1] . "\n        '" . $key . "' => " . $literal . ',' . $m[2],
                    $code,
                    1
                );
            }
        }
        if (!is_writable($path)) {
            @chmod($path, 0664);
        }
        if ($code === '' || @file_put_contents($path, $code, LOCK_EX) === false) {
            self::override($values);
            return false;
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
        unset($_SESSION['env_override']);
        return true;
    }

    /**
     * Uses these settings for the rest of this browser session without saving
     * them — so setup can carry on when Database.php can't be written. Tied to
     * the file's current settings: once Database.php is edited, it is dropped.
     */
    public static function override(array $values): void
    {
        $current = $_SESSION['env_override'] ?? [];
        if (($current['_for'] ?? '') !== self::fingerprint()) {
            $current = ['_for' => self::fingerprint()];
        }
        $_SESSION['env_override'] = array_merge($current, array_map('strval', $values));
    }

    private static function fingerprint(): string
    {
        return md5(serialize(Database::SETTINGS));
    }

    /** Creates the folder if needed and tries to make it writable; never fails. */
    public static function makeWritable(string $dir): bool
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0775);
        }
        return is_writable($dir);
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $override = $_SESSION['env_override'] ?? null;
            if (is_array($override) && ($override['_for'] ?? '') === self::fingerprint() && array_key_exists($key, $override)) {
                $value = $override[$key];
            } else {
                $value = self::$runtime[$key] ?? Database::SETTINGS[$key] ?? null;
            }
        }
        return $value === null || $value === '' ? $default : $value;
    }
}
