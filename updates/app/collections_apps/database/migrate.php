<?php
/**
 * Migration runner.
 *   php app/collections_apps/database/migrate.php            Run all pending migrations
 *   php app/collections_apps/database/migrate.php --fresh    Drop all known tables, then run every migration
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load();
$pdo = Database::connection();

$fresh = in_array('--fresh', $argv, true);

if ($fresh) {
    echo "Dropping existing tables...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['order_items', 'orders', 'otp_codes', 'customers', 'products', 'categories', 'admins', 'seo_meta', 'store_settings', 'migrations'] as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file, '.php');
    if (in_array($name, $applied, true)) {
        continue;
    }
    $migration = require $file;
    echo "Migrating: {$name}\n";
    $pdo->exec($migration['up']);
    $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)')->execute([$name]);
    $ran++;
}

echo $ran ? "Ran {$ran} migration(s).\n" : "Nothing to migrate.\n";
