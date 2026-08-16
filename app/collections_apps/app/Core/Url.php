<?php

namespace App\Core;

/**
 * Computes the app's base path once per request (e.g. "/Pentagon-collections/public"
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
        // dirname(SCRIPT_NAME) for public/index.php is the folder the browser sees it in.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        self::$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
    }

    public static function basePath(): string
    {
        self::init();
        return self::$basePath;
    }

    /** Absolute app URL for a route, e.g. Url::to('/admin/products') */
    public static function to(string $path = '/'): string
    {
        self::init();
        return self::$basePath . '/' . ltrim($path, '/');
    }

    /** Absolute app URL for a static file under public/, e.g. Url::asset('assets/css/app.css') */
    public static function asset(string $path): string
    {
        return self::to($path);
    }

    /**
     * The current request path relative to the app base (e.g. "/admin/products"),
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
