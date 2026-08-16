<?php
/**
 * Application bootstrap — required once by public/index.php (the only PHP
 * file Apache ever executes; see public/.htaccess) before the router runs.
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/Helpers/functions.php';

App\Core\Env::load();
App\Core\Url::init();

error_reporting(E_ALL);
ini_set('display_errors', App\Core\Env::get('APP_DEBUG', '1') === '1' ? '1' : '0');
