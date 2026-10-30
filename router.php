<?php
/**
 * Root router so the app serves cleanly from the project root — e.g.
 *
 *     php -S localhost:8004 router.php
 *
 * opens at http://localhost:8004 (not .../public). Real files under public/
 * (CSS, JS, images, uploads) are served directly; everything else is handed
 * to the front controller in public/index.php.
 *
 * On normal hosting you don't need this file: point the (sub)domain's
 * document root at the public/ folder, or use the root .htaccess, which does
 * the same mapping for Apache. See HOSTING.md.
 */

$publicDir = __DIR__ . '/public';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = urldecode($uri);

// Serve an existing static file from public/ (but never a PHP script directly).
if ($uri !== '/' && strpos($uri, '..') === false) {
    $candidate = $publicDir . $uri;
    $real = realpath($candidate);
    $base = realpath($publicDir);
    if ($real !== false && $base !== false && is_file($real)
        && strpos($real, $base . DIRECTORY_SEPARATOR) === 0
        && strtolower((string) pathinfo($real, PATHINFO_EXTENSION)) !== 'php') {
        $ext = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
        $types = [
            'css' => 'text/css', 'js' => 'text/javascript', 'mjs' => 'text/javascript',
            'json' => 'application/json', 'webmanifest' => 'application/manifest+json',
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
            'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject',
            'pdf' => 'application/pdf', 'txt' => 'text/plain', 'map' => 'application/json',
            'mp4' => 'video/mp4', 'webm' => 'video/webm',
        ];
        if (isset($types[$ext])) {
            header('Content-Type: ' . $types[$ext]);
        } elseif (function_exists('mime_content_type') && ($m = mime_content_type($real))) {
            header('Content-Type: ' . $m);
        }
        readfile($real);
        exit;
    }
}

// Everything else: run the application's single front controller.
require $publicDir . '/index.php';
