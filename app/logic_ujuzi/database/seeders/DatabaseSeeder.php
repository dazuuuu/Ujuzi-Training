<?php
/**
 * One-time/idempotent seed: the first admin account
 * (from ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in app/Core/Database.php).
 * Run: php app/logic_ujuzi/database/seeders/DatabaseSeeder.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load();
$pdo = Database::connection();

// --- Admin account ---
$adminEmail = Env::get('ADMIN_SEED_EMAIL', 'admin@example.com');
$adminPass = Env::get('ADMIN_SEED_PASSWORD', 'admin123');

$exists = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
$exists->execute([$adminEmail]);
if (!$exists->fetch()) {
    $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)')
        ->execute([$adminEmail, password_hash($adminPass, PASSWORD_DEFAULT)]);
    echo "Created admin account '{$adminEmail}'.\n";
} else {
    echo "Admin account '{$adminEmail}' already exists — left untouched.\n";
}

echo "Seed complete.\n";
