<?php

namespace App\Core;

/**
 * The app's error log: storage/logs/error.log, outside the web root. PHP
 * warnings and uncaught exceptions are written here (see bootstrap.php) and
 * a main Super Admin reads it from Admin → Error logs.
 */
class ErrorLog
{
    /** Past this size the old log is moved to error.log.1 so the file stays readable. */
    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function path(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/error.log';
        if (is_file($file) && filesize($file) > self::MAX_BYTES) {
            @rename($file, $file . '.1');
        }
        return $file;
    }

    /** Logs an uncaught exception with the page and user that hit it. */
    public static function exception(\Throwable $e): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $who = isset($_SESSION['admin_id']) ? ' admin#' . $_SESSION['admin_id'] : (isset($_SESSION['user_id']) ? ' user#' . $_SESSION['user_id'] : '');
        error_log("Uncaught on {$method} {$uri}{$who}\n" . $e);
    }

    /**
     * Logs a page nobody's route matched — a dead link, a stray /public/ in
     * a URL, a typo — so it shows here too, not just as a silent 404 to the
     * visitor. Never throws; a logging failure must not break the 404 page.
     */
    public static function notFound(string $method, string $uri): void
    {
        try {
            $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            error_log("404 on {$method} {$uri}" . ($ref !== '' ? " (from {$ref})" : '') . ($ip !== '' ? " [{$ip}]" : ''));
        } catch (\Throwable $e) {
            // never block the 404 page over a logging failure
        }
    }

    /** The last $lines lines, newest entry last. */
    public static function tail(int $lines = 400): string
    {
        $file = self::path();
        if (!is_file($file)) {
            return '';
        }
        $size = (int) filesize($file);
        $fh = $size > 0 ? @fopen($file, 'rb') : false;
        if (!$fh) {
            return ''; // empty or unreadable log: nothing to show (fread() with length 0 throws in PHP 8)
        }
        $chunk = min($size, 512 * 1024);
        fseek($fh, -$chunk, SEEK_END);
        $text = $chunk > 0 ? (string) fread($fh, $chunk) : '';
        fclose($fh);
        return implode("\n", array_slice(explode("\n", rtrim($text)), -$lines));
    }

    public static function size(): int
    {
        $file = self::path();
        return is_file($file) ? (int) filesize($file) : 0;
    }

    public static function clear(): void
    {
        @file_put_contents(self::path(), '');
    }
}
