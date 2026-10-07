<?php

namespace App\Core;

/**
 * Computes the app's base path once per request (e.g. "/Ujuzi/public"
 * when hosted in a subfolder, or "" at domain root) so every generated link/asset
 * URL works regardless of where the vhost points.
 */
class Url
{
    private static ?string $basePath = null;

    public static function init(): void
    {
        if (self::$basePath !== null) {
            return;
        }

        // Explicit override: set APP_URL in app/Core/Database.php to the exact URL the app is
        // hosted at (e.g. https://dazuaihub.com/Ujuzi/public). This is the
        // single place to fix broken links/permalinks after hosting — when
        // it's set, every link, form action, and asset URL the app generates
        // is anchored to it instead of being guessed from server variables
        // (which shared hosts, subfolders, and proxies frequently get wrong).
        $configured = trim((string) Env::get('APP_URL', ''));
        if ($configured !== '') {
            $configuredPath = (string) (parse_url($configured, PHP_URL_PATH) ?? '');
            self::$basePath = rtrim($configuredPath, '/');
            return;
        }

        // Fallback: dirname(SCRIPT_NAME) for public/index.php is the folder the browser sees it in.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        self::$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
    }

    public static function basePath(): string
    {
        self::init();
        return self::$basePath;
    }

    /** Absolute app URL for a route, e.g. Url::to('/admin/users') */
    public static function to(string $path = '/'): string
    {
        self::init();
        return self::$basePath . '/' . ltrim($path, '/');
    }

    /**
     * Full URL including the current scheme, domain, and port
     * (e.g. http://localhost:8000/register/organisation-admin/...).
     */
    public static function absolute(string $path = '/'): string
    {
        $configured = trim((string) Env::get('APP_URL', ''));
        if ($configured !== '') {
            $scheme = (string) (parse_url($configured, PHP_URL_SCHEME) ?? 'https');
            $host = (string) (parse_url($configured, PHP_URL_HOST) ?? '');
            $port = parse_url($configured, PHP_URL_PORT);
            if ($host !== '') {
                $host .= $port ? ':' . $port : '';
                return $scheme . '://' . $host . self::to($path);
            }
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        return $scheme . '://' . $host . self::to($path);
    }

    /** Absolute app URL for a static file under public/, e.g. Url::asset('assets/css/app.css') */
    public static function asset(string $path): string
    {
        return self::to($path);
    }

    /**
     * The current request path relative to the app base (e.g. "/admin/users"),
     * with the query string stripped and a leading slash guaranteed.
     */
    public static function currentPath(): string
    {
        self::init();
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (self::$basePath !== '' && strpos($uri, self::$basePath) === 0) {
            $uri = substr($uri, strlen(self::$basePath));
        }
        $uri = '/' . ltrim($uri, '/');
        return rtrim($uri, '/') === '' ? '/' : rtrim($uri, '/');
    }
}
