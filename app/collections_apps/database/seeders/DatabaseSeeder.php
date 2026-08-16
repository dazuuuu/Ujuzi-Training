<?php
/**
 * One-time/idempotent seed: categories, products, and the first admin account
 * (from ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in .env).
 * Run: php app/collections_apps/database/seeders/DatabaseSeeder.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load();
$pdo = Database::connection();

// --- Categories ---
$categories = require __DIR__ . '/data/categories.php';
$catStmt = $pdo->prepare(
    'INSERT INTO categories (category_key, name, tagline, image, sort_order)
     VALUES (:key, :name, :tagline, :image, :sort)
     ON DUPLICATE KEY UPDATE name = VALUES(name), tagline = VALUES(tagline), image = VALUES(image), sort_order = VALUES(sort_order)'
);
foreach ($categories as $c) {
    $catStmt->execute(['key' => $c['key'], 'name' => $c['name'], 'tagline' => $c['tagline'], 'image' => $c['image'], 'sort' => $c['sort']]);
}
echo 'Seeded ' . count($categories) . " categories.\n";

// --- Products ---
$products = require __DIR__ . '/data/products.php';
$prodStmt = $pdo->prepare(
    'INSERT INTO products (
        product_code, name, subtitle, price, original_price, category_key, sub_category,
        description, details, fabric, fit, sizes, colors, images,
        is_new, is_best_seller, is_sale, in_stock, featured_in_lookbook, rating, review_count
     ) VALUES (
        :product_code, :name, :subtitle, :price, :original_price, :category_key, :sub_category,
        :description, :details, :fabric, :fit, :sizes, :colors, :images,
        :is_new, :is_best_seller, :is_sale, :in_stock, :featured_in_lookbook, :rating, :review_count
     )
     ON DUPLICATE KEY UPDATE
        name = VALUES(name), subtitle = VALUES(subtitle), price = VALUES(price), original_price = VALUES(original_price),
        category_key = VALUES(category_key), sub_category = VALUES(sub_category), description = VALUES(description),
        details = VALUES(details), fabric = VALUES(fabric), fit = VALUES(fit), sizes = VALUES(sizes),
        colors = VALUES(colors), images = VALUES(images), is_new = VALUES(is_new), is_best_seller = VALUES(is_best_seller),
        is_sale = VALUES(is_sale), in_stock = VALUES(in_stock), featured_in_lookbook = VALUES(featured_in_lookbook),
        rating = VALUES(rating), review_count = VALUES(review_count)'
);

function seedNormalizeColors(array $colors): array
{
    return array_map(fn($c) => ['name' => $c['name'], 'hex' => $c['hex'], 'image' => null], $colors);
}

foreach ($products as $p) {
    $prodStmt->execute([
        'product_code' => $p['id'],
        'name' => $p['name'],
        'subtitle' => $p['subtitle'] ?? null,
        'price' => $p['price'],
        'original_price' => $p['originalPrice'] ?? null,
        'category_key' => $p['category'],
        'sub_category' => $p['subCategory'] ?? null,
        'description' => $p['description'] ?? null,
        'details' => json_encode($p['details'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'fabric' => $p['fabric'] ?? null,
        'fit' => $p['fit'] ?? null,
        'sizes' => json_encode($p['sizes'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'colors' => json_encode(seedNormalizeColors($p['colors'] ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'images' => json_encode($p['images'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'is_new' => !empty($p['isNew']) ? 1 : 0,
        'is_best_seller' => !empty($p['isBestSeller']) ? 1 : 0,
        'is_sale' => !empty($p['isSale']) ? 1 : 0,
        'in_stock' => !empty($p['inStock']) ? 1 : 0,
        'featured_in_lookbook' => !empty($p['featuredInLookbook']) ? 1 : 0,
        'rating' => $p['rating'] ?? 5.0,
        'review_count' => $p['reviewCount'] ?? 0,
    ]);
}
echo 'Seeded ' . count($products) . " products.\n";

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
