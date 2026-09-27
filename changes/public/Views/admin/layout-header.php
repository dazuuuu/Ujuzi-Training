<?php
/**
 * Shared super-admin shell (sidebar + topbar). Include after setting:
 *   $pageTitle  — shown in <title> and the topbar
 *   $activeNav  — one of: dashboard, roles, organisations, share, registered-users,
 *                 students, attachment-providers, course-organisations, forms, settings
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
    ['id' => 'students', 'href' => url('/admin/registered-users?role_slug=student'), 'label' => 'Students'],
    ['id' => 'attachment-providers', 'href' => url('/admin/registered-users?role_slug=attachment_trainer'), 'label' => 'Attachment providers'],
    ['id' => 'course-organisations', 'href' => url('/admin/registered-users?role_slug=organisation_admin'), 'label' => 'Organisations providing courses'],
    ['id' => 'forms', 'href' => url('/admin/forms'), 'label' => 'Forms'],
    ['id' => 'updates', 'href' => url('/admin/updates'), 'label' => 'Updates'],
    ['id' => 'attachments', 'href' => url('/admin/attachments'), 'label' => 'Attachments'],
    ['id' => 'finance', 'href' => url('/admin/finance'), 'label' => 'Finance'],
    ['id' => 'documents', 'href' => url('/admin/documents'), 'label' => 'Documents'],
    ['id' => 'data-cleanup', 'href' => url('/admin/data-cleanup'), 'label' => 'Data Cleanup'],
    ['id' => 'navigation', 'href' => url('/admin/navigation'), 'label' => 'Navigation'],
    ['id' => 'homepage', 'href' => url('/admin/homepage'), 'label' => 'Homepage'],
    ['id' => 'settings', 'href' => url('/admin/settings'), 'label' => 'Settings'],
    ['id' => 'admins', 'href' => url('/admin/admins'), 'label' => 'Admins'],
];
$navIcons = [
    'dashboard' => 'dashboard', 'roles' => 'settings', 'organisations' => 'building', 'share' => 'arrow-right',
    'registered-users' => 'users', 'students' => 'graduation', 'attachment-providers' => 'briefcase',
    'course-organisations' => 'building', 'forms' => 'file', 'updates' => 'download', 'attachments' => 'briefcase',
    'finance' => 'wallet', 'documents' => 'file', 'data-cleanup' => 'x', 'navigation' => 'menu', 'settings' => 'settings',
    'homepage' => 'dashboard', 'admins' => 'user',
];
$admin = AdminSession::current();
// Limited admins only see the sections they were given.
if ($admin) {
    $navItems = array_values(array_filter($navItems, static fn(array $item): bool => \App\Core\AdminAccess::allowsNav($admin, $item['id'])));
}
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title><?= e($pageTitle ?? 'Admin') ?> | <?= e(appName()) ?> Super Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app-shell.css') ?>">
</head>
<body class="admin-panel antialiased h-dvh flex flex-col overflow-hidden">
  <div class="flag-stripe" aria-hidden="true"></div>
  <div class="workspace-shell flex flex-1 min-h-0 admin-shell">

  <!-- Sidebar -->
  <div class="admin-sidebar-backdrop fixed inset-0 bg-neutral-900/50 z-40 hidden md:hidden" id="adminSidebarBackdrop"></div>
  <aside class="workspace-sidebar admin-sidebar fixed md:static inset-y-0 left-0 z-50 w-64 shrink-0 h-screen overflow-y-auto bg-white border-r border-neutral-200 flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-transform duration-300" id="adminSidebar">
    <div>
      <div class="workspace-brand flex items-center justify-between px-5 py-5 border-b">
        <div class="flex items-center gap-2.5">
          <div class="workspace-logo w-8 h-8 flex items-center justify-center rounded-lg shrink-0" style="background:var(--ke-green);color:white;">
            <?= storeLogoHtml('w-full h-full object-contain rounded-lg', 'w-4 h-4 text-white') ?>
          </div>
          <div class="flex flex-col leading-none">
            <span class="font-serif-heading text-sm font-extrabold tracking-[0.15em] uppercase" style="color:var(--ke-green-dark)"><?= e(appName()) ?></span>
            <span class="text-[8px] tracking-[0.25em] uppercase mt-0.5 font-bold workspace-muted">Super Admin</span>
          </div>
        </div>
        <button type="button" class="md:hidden text-neutral-500 text-xl" id="adminSidebarClose" aria-label="Close menu"><?= icon('x') ?></button>
      </div>

      <nav class="workspace-nav p-3 space-y-1 text-xs font-semibold uppercase tracking-wider">
        <?php foreach ($navItems as $item): $active = ($activeNav ?? '') === $item['id']; ?>
          <a href="<?= e($item['href']) ?>" class="workspace-nav-link admin-nav-link block px-3 py-2.5 rounded-lg transition-colors <?= $active ? 'admin-nav-active font-bold is-active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <?= icon($navIcons[$item['id']] ?? 'more', 'h-4 w-4') ?> <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>

    <div class="workspace-sidebar-footer p-4 border-t text-xs">
      <p class="font-semibold workspace-muted">Signed in as <?= !empty($admin['is_owner']) ? 'Super Admin' : 'Admin (limited access)' ?></p>
      <p class="font-bold mb-2 truncate"><?= e($admin['email'] ?? '') ?></p>
      <div class="flex flex-col gap-1.5">
        <a href="<?= url('/') ?>" target="_blank" class="font-semibold workspace-link">View LMS &rarr;</a>
        <a href="<?= url('/admin/logout') ?>" class="btn-danger" style="padding:0.45rem 0.7rem;">Sign out</a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="workspace-main flex-1 flex flex-col min-w-0 h-full admin-main">
    <header class="workspace-topbar shrink-0 bg-white border-b px-6 py-4 flex items-center justify-between gap-4" style="border-color:var(--ke-line)">
      <div class="flex items-center gap-3">
        <button type="button" class="md:hidden text-neutral-700" id="adminMenuToggle" aria-label="Open menu"><?= icon('menu', 'h-6 w-6') ?></button>
        <h1 class="font-serif-heading text-xl font-bold" style="color:var(--ke-ink)"><?= e($pageTitle ?? '') ?></h1>
      </div>
      <div class="workspace-topbar-tools">
        <label class="workspace-search" aria-label="Search">
          <span aria-hidden="true" class="text-neutral-400"><?= icon('search', 'h-4 w-4') ?></span>
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
    <nav class="app-tabbar is-admin" aria-label="Main">
      <?php foreach ([['dashboard', 'Home'], ['attachments', 'Attachments'], ['finance', 'Finance'], ['registered-users', 'Users']] as [$tabId, $tabLabel]):
        $tab = null;
        foreach ($navItems as $item) { if ($item['id'] === $tabId) { $tab = $item; break; } }
        if (!$tab) { continue; }
        $isHere = ($activeNav ?? '') === $tabId;
      ?>
        <a href="<?= e($tab['href']) ?>" class="app-tab <?= $isHere ? 'is-active' : '' ?>" <?= $isHere ? 'aria-current="page"' : '' ?>>
          <?= icon($navIcons[$tabId] ?? 'more', 'h-6 w-6') ?><span><?= e($tabLabel) ?></span>
        </a>
      <?php endforeach; ?>
      <button type="button" class="app-tab" onclick="document.getElementById('adminMenuToggle').click()" aria-label="More — open the full menu">
        <?= icon('more', 'h-6 w-6') ?><span>More</span>
      </button>
    </nav>
    <div class="app-progress" id="appProgress" aria-hidden="true"></div>
    <main class="workspace-content p-6 flex-1 overflow-y-auto">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="flash-error"><?= e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>
<script>
(function(){
  var sidebar  = document.getElementById('adminSidebar');
  var backdrop = document.getElementById('adminSidebarBackdrop');
  var toggle   = document.getElementById('adminMenuToggle');
  var closeBtn = document.getElementById('adminSidebarClose');
  function openNav(){
    if(sidebar)  sidebar.classList.remove('-translate-x-full');
    if(backdrop){ backdrop.classList.remove('hidden'); backdrop.classList.add('block'); }
    document.body.style.overflow = 'hidden';
  }
  function closeNav(){
    if(sidebar)  sidebar.classList.add('-translate-x-full');
    if(backdrop){ backdrop.classList.add('hidden'); backdrop.classList.remove('block'); }
    document.body.style.overflow = '';
  }
  if(toggle)   toggle.addEventListener('click', openNav);
  if(closeBtn) closeBtn.addEventListener('click', closeNav);
  if(backdrop) backdrop.addEventListener('click', closeNav);
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeNav(); });
  window.addEventListener('resize', function(){ if(window.innerWidth >= 768) closeNav(); });
})();
</script>
