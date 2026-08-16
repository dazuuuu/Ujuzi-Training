<?php
/**
 * Shared admin shell (sidebar + topbar). Include after setting:
 *   $pageTitle  — shown in <title> and the topbar
 *   $activeNav  — one of: dashboard, products, product-form, categories, offers, orders, seo, settings
 * Requires App\Core\AdminSession::require() to have already run.
 */

use App\Core\AdminSession;
use App\Services\MigrationService;

$navItems = [
    ['id' => 'dashboard', 'href' => url('/admin'), 'label' => 'Dashboard'],
    ['id' => 'products', 'href' => url('/admin/products'), 'label' => 'Products'],
    ['id' => 'product-form', 'href' => url('/admin/products/create'), 'label' => 'Add Product'],
    ['id' => 'offers', 'href' => url('/admin/offers'), 'label' => 'Offers'],
    ['id' => 'categories', 'href' => url('/admin/categories'), 'label' => 'Categories'],
    ['id' => 'orders', 'href' => url('/admin/orders'), 'label' => 'Orders'],
    ['id' => 'seo', 'href' => url('/admin/seo'), 'label' => 'SEO'],
    ['id' => 'settings', 'href' => url('/admin/settings'), 'label' => 'Settings'],
];
$admin = AdminSession::current();
$pendingMigrations = [];
try {
    $pendingMigrations = MigrationService::pending();
} catch (Throwable $e) {
    $pendingMigrations = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'Admin') ?> | Pentagon Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="admin-panel bg-white text-[#1a1a1a] antialiased min-h-screen flex">

  <!-- Sidebar -->
  <aside class="w-60 shrink-0 bg-white text-black min-h-screen flex flex-col justify-between border-r border-black sticky top-0 self-start h-screen overflow-y-auto">
    <div>
      <div class="flex items-center gap-2.5 px-5 py-5 border-b border-black">
        <div class="w-8 h-8 bg-[#0a0a0a] text-white flex items-center justify-center rounded-lg border border-black shrink-0">
          <?= storeLogoHtml('w-full h-full object-contain rounded-lg', 'w-4 h-4 text-white') ?>
        </div>
        <div class="flex flex-col leading-none">
          <span class="font-serif-heading text-sm font-extrabold tracking-[0.15em] uppercase text-black">PENTAGON</span>
          <span class="text-[8px] tracking-[0.25em] text-neutral-700 uppercase mt-0.5 font-bold">Admin Panel</span>
        </div>
      </div>

      <nav class="p-3 space-y-1 text-xs font-semibold uppercase tracking-wider">
        <?php foreach ($navItems as $item): $active = ($activeNav ?? '') === $item['id']; ?>
          <a href="<?= e($item['href']) ?>" class="admin-nav-link block px-3 py-2.5 rounded-lg transition-colors <?= $active ? 'admin-nav-active bg-black text-white font-bold' : 'text-neutral-800 hover:bg-black hover:text-white' ?>">
            <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="p-4 border-t border-black text-xs">
      <p class="text-neutral-700 font-semibold">Signed in as</p>
      <p class="font-bold text-black mb-2 truncate"><?= e($admin['email'] ?? '') ?></p>
      <div class="flex flex-col gap-1.5">
        <a href="<?= url('/') ?>" target="_blank" class="text-neutral-800 hover:text-black font-semibold">View storefront &rarr;</a>
        <a href="<?= url('/admin/logout') ?>" class="text-rose-700 hover:text-rose-900 font-bold">Sign out</a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 min-w-0">
    <header class="bg-white border-b border-neutral-200 px-6 py-4 sticky top-0 z-10 flex items-center justify-between gap-4">
      <h1 class="font-serif-heading text-xl font-bold text-[#0a0a0a]"><?= e($pageTitle ?? '') ?></h1>
      <?php if ($pendingMigrations): ?>
        <a href="<?= url('/admin/updates') ?>" class="inline-flex items-center gap-2 bg-black text-white text-[11px] font-bold px-3 py-2 rounded-lg uppercase tracking-widest hover:bg-neutral-900 transition-colors">
          <span>Update</span>
          <span class="bg-white text-black rounded-full px-1.5 py-0.5 text-[10px] leading-none"><?= count($pendingMigrations) ?></span>
        </a>
      <?php endif; ?>
    </header>
    <main class="p-6">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="mb-5 bg-neutral-50 border border-neutral-300 text-black text-sm font-semibold rounded-lg p-3">
          <?= e($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-5 bg-rose-50 border border-rose-300 text-rose-800 text-sm font-semibold rounded-lg p-3">
          <?= e($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>
