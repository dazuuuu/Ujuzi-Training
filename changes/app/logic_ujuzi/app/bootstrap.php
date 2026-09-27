<?php
/**
 * Application bootstrap — required once by public/index.php (the only PHP
 * file Apache ever executes; see public/.htaccess) before the router runs.
 * This bundle now powers the Ujuzi Training LMS rather than a storefront.
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Helpers/functions.php';

App\Core\Env::load();
App\Core\Url::init();

date_default_timezone_set(App\Core\Env::get('APP_TIMEZONE', 'Africa/Nairobi'));

error_reporting(E_ALL);
ini_set('display_errors', App\Core\Env::get('APP_DEBUG', '1') === '1' ? '1' : '0');

// A page that needs a table or column from a migration not yet run shows a
// clear "run the updates" page instead of a fatal error. Anything else is
// shown in full when APP_DEBUG is on, and logged.
set_exception_handler(static function (Throwable $e): void {
    $needsUpdate = $e instanceof PDOException && in_array((string) $e->getCode(), ['42S02', '42S22'], true);
    if (!headers_sent()) {
        http_response_code($needsUpdate ? 503 : 500);
        header('Content-Type: text/html; charset=utf-8');
    }
    error_log((string) $e);
    if ($needsUpdate) {
        $updates = htmlspecialchars(url('/admin/updates'));
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Update needed</title></head><body style="font-family:system-ui,sans-serif;background:#f5f7f9;margin:0;padding:24px">'
            . '<div style="max-width:520px;margin:10vh auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px">'
            . '<h1 style="font-size:20px;margin:0 0 8px">This page needs a database update</h1>'
            . '<p style="color:#4b5563;line-height:1.5;margin:0 0 20px">New features were installed but the database has not been updated yet. '
            . 'A Super Admin can run the pending updates once from <strong>Updates</strong>; then this page works.</p>'
            . '<a href="' . $updates . '" style="display:inline-block;background:#006b3f;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:700">Open Updates</a>'
            . '</div></body></html>';
        return;
    }
    if (ini_get('display_errors')) {
        echo '<pre style="white-space:pre-wrap;padding:16px">' . htmlspecialchars((string) $e) . '</pre>';
    } else {
        echo 'Something went wrong. Please try again.';
    }
});
