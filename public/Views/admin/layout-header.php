<?php
/**
 * Shared super-admin shell (sidebar + topbar). Include after setting:
 *   $pageTitle  — shown in <title> and the topbar
 *   $activeNav  — one of: dashboard, roles, organisations, share, registered-users, forms, settings
 * Requires App\Core\AdminSession::require() to have already run.
 */

use App\Core\AdminSession;
use App\Services\MigrationService;

$navItems = [
    ['id' => 'dashboard', 'href' => url('/admin'), 'label' => 'Dashboard'],
    ['id' => 'roles', 'href' => url('/admin/roles'), 'label' => 'Roles'],
    ['id' => 'organisations', 'href' => url('/admin/organisations'), 'label' => 'Organisations'],
    ['id' => 'share', 'href' => url('/admin/share-registration'), 'label' => 'Share registration'],
    ['id' => 'registered-users', 'href' => url('/admin/registered-users'), 'label' => 'Registered users'],
    ['id' => 'forms', 'href' => url('/admin/forms'), 'label' => 'Forms'],
    ['id' => 'updates', 'href' => url('/admin/updates'), 'label' => 'Updates'],
    ['id' => 'certificate', 'href' => url('/admin/certificate'), 'label' => 'Certificate'],
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
  <title><?= e($pageTitle ?? 'Admin') ?> | <?= e(appName()) ?> Super Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="admin-panel antialiased min-h-screen flex flex-col">
  <div class="flag-stripe" aria-hidden="true"></div>
  <div class="workspace-shell flex flex-1 min-h-0 admin-shell">

  <!-- Sidebar -->
  <aside class="workspace-sidebar w-60 shrink-0 min-h-screen flex flex-col justify-between sticky top-0 self-start h-screen overflow-y-auto admin-sidebar">
    <div>
      <div class="workspace-brand flex items-center gap-2.5 px-5 py-5 border-b">
        <div class="workspace-logo w-8 h-8 flex items-center justify-center rounded-lg shrink-0">
          <?= storeLogoHtml('w-full h-full object-contain rounded-lg', 'w-4 h-4 text-white') ?>
        </div>
        <div class="flex flex-col leading-none">
          <span class="font-serif-heading text-sm font-extrabold tracking-[0.15em] uppercase"><?= e(appName()) ?></span>
          <span class="text-[8px] tracking-[0.25em] uppercase mt-0.5 font-bold workspace-muted">Super Admin</span>
        </div>
      </div>

      <nav class="workspace-nav p-3 space-y-1 text-xs font-semibold uppercase tracking-wider">
        <?php foreach ($navItems as $item): $active = ($activeNav ?? '') === $item['id']; ?>
          <a href="<?= e($item['href']) ?>" class="workspace-nav-link admin-nav-link block px-3 py-2.5 rounded-lg transition-colors <?= $active ? 'admin-nav-active font-bold is-active' : '' ?>">
            <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="workspace-sidebar-footer p-4 border-t text-xs">
      <p class="font-semibold workspace-muted">Signed in as Super Admin</p>
      <p class="font-bold mb-2 truncate"><?= e($admin['email'] ?? '') ?></p>
      <div class="flex flex-col gap-1.5">
        <a href="<?= url('/') ?>" target="_blank" class="font-semibold workspace-link">View LMS &rarr;</a>
        <a href="<?= url('/admin/logout') ?>" class="btn-danger" style="padding:0.45rem 0.7rem;">Sign out</a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="workspace-main flex-1 min-w-0 admin-main">
    <header class="workspace-topbar bg-white border-b px-6 py-4 sticky top-0 z-10 flex items-center justify-between gap-4" style="border-color:var(--ke-line)">
      <h1 class="font-serif-heading text-xl font-bold" style="color:var(--ke-ink)"><?= e($pageTitle ?? '') ?></h1>
      <div class="workspace-topbar-tools">
        <label class="workspace-search" aria-label="Search">
          <span aria-hidden="true">⌕</span>
          <input type="search" placeholder="Search..." />
        </label>
      <?php if ($pendingMigrations): ?>
        <a href="<?= url('/admin/updates') ?>" class="btn-danger">
          <span>Update</span>
          <span class="bg-white rounded-full px-1.5 py-0.5 text-[10px] leading-none" style="color:var(--ke-red)"><?= count($pendingMigrations) ?></span>
        </a>
      <?php endif; ?>
      </div>
    </header>
    <main class="workspace-content p-6">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="flash-error"><?= e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>
