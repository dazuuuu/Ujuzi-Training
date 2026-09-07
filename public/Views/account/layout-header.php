<?php
/**
 * Shared LMS user-account shell. Include after setting $pageTitle and optional $activeNav.
 */
use App\Core\UserSession;
$loggedInUser = $currentUser ?? UserSession::current();
$canManageUsers = $canManageUsers ?? false;
$isOrgAdmin = $isOrgAdmin ?? false;
$canManageBranches = $canManageBranches ?? $isOrgAdmin;
$canViewCourses = $canViewCourses ?? false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle ?? 'My Account') ?> | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="antialiased min-h-screen flex flex-col" style="background:var(--ke-paper);color:var(--ke-ink)">
  <div class="flag-stripe" aria-hidden="true"></div>

  <?php if ($loggedInUser): ?>
  <div class="workspace-shell account-shell flex-1 min-h-0">
    <aside class="workspace-sidebar account-sidebar flex flex-col justify-between">
      <div>
        <div class="workspace-brand flex items-center gap-2.5 px-5 py-5 border-b">
          <a href="<?= url('/account') ?>" class="workspace-logo w-8 h-8 flex items-center justify-center rounded-lg shrink-0">
            <?= storeLogoHtml('w-full h-full object-contain rounded-lg', 'w-4 h-4 text-white') ?>
          </a>
          <div class="flex flex-col leading-none">
            <span class="font-serif-heading text-sm font-extrabold tracking-[0.15em] uppercase"><?= e(appName()) ?></span>
            <span class="text-[8px] tracking-[0.25em] uppercase mt-0.5 font-bold workspace-muted"><?= e($loggedInUser['role_name'] ?? 'Account') ?></span>
          </div>
        </div>
        <div class="workspace-user px-5 py-4">
          <div class="workspace-avatar"><?= e(strtoupper(substr((string) ($loggedInUser['first_name'] ?? $loggedInUser['email'] ?? 'U'), 0, 1))) ?></div>
          <p class="mt-2 text-sm font-bold truncate"><?= e(userDisplayName($loggedInUser)) ?></p>
          <p class="text-xs workspace-muted truncate"><?= e($loggedInUser['email'] ?? $loggedInUser['phone'] ?? '') ?></p>
        </div>
        <nav class="workspace-nav p-3 space-y-1 text-xs font-semibold uppercase tracking-wider">
          <a href="<?= url('/account') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'landing' ? 'is-active' : '' ?>">Home</a>
          <a href="<?= url('/account/dashboard') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'is-active' : '' ?>">Dashboard</a>
          <a href="<?= url('/account/profile') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'profile' ? 'is-active' : '' ?>">Profile</a>
          <?php if ($canManageUsers): ?><a href="<?= url('/account/people') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'people' ? 'is-active' : '' ?>">People</a><?php endif; ?>
          <?php if ($isOrgAdmin): ?><a href="<?= url('/account/categories') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'categories' ? 'is-active' : '' ?>">Categories</a><?php endif; ?>
          <?php if ($canManageBranches): ?><a href="<?= url('/account/branches') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'branches' ? 'is-active' : '' ?>">Branches</a><?php endif; ?>
          <?php if ($canViewCourses): ?><a href="<?= url('/account/courses') ?>" class="workspace-nav-link <?= ($activeNav ?? '') === 'courses' ? 'is-active' : '' ?>">Courses</a><?php endif; ?>
        </nav>
      </div>
      <div class="workspace-sidebar-footer p-4 border-t text-xs">
        <a href="<?= url('/') ?>" class="workspace-link font-semibold">View LMS</a>
        <a href="<?= url('/account/logout') ?>" class="btn-danger mt-2 block text-center" style="padding:0.45rem 0.7rem;">Sign out</a>
      </div>
    </aside>
    <div class="workspace-main flex-1 min-w-0">
      <header class="workspace-topbar account-header bg-white border-b px-6 py-4 flex items-center justify-between gap-4">
        <h1 class="font-serif-heading text-xl font-bold"><?= e($pageTitle ?? 'My Account') ?></h1>
        <label class="workspace-search" aria-label="Search"><span aria-hidden="true">⌕</span><input type="search" placeholder="Search..." /></label>
      </header>
  <?php else: ?>
  <header class="w-full py-3 sm:py-4 text-white account-header" style="background:var(--ke-black)">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 group">
        <div class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-md shrink-0" style="background:var(--ke-red)">
          <?= storeLogoHtml('w-full h-full object-contain rounded-md', 'w-4 h-4 text-white') ?>
        </div>
        <div class="flex flex-col text-left leading-none">
          <span class="font-serif-heading text-base sm:text-lg font-extrabold tracking-[0.18em] text-white uppercase"><?= e(appName()) ?></span>
          <span class="text-[8px] tracking-[0.3em] font-sans font-semibold uppercase mt-0.5" style="color:#b8e0cc">
            <?= $loggedInUser ? e($loggedInUser['role_name'] ?? 'Account') : 'Sign in' ?>
          </span>
        </div>
      </a>
      <nav class="flex flex-wrap items-center justify-end gap-3 text-xs font-bold uppercase tracking-wider account-nav">
        <?php if ($loggedInUser): ?>
          <a href="<?= url('/account/dashboard') ?>" class="<?= ($activeNav ?? '') === 'dashboard' ? 'text-white' : '' ?>" style="color: <?= ($activeNav ?? '') === 'dashboard' ? '#ffffff' : '#b8e0cc' ?>">Dashboard</a>
          <a href="<?= url('/account/profile') ?>" style="color: <?= ($activeNav ?? '') === 'profile' ? '#ffffff' : '#b8e0cc' ?>">Profile</a>
          <?php if ($canManageUsers): ?>
            <a href="<?= url('/account/people') ?>" style="color: <?= ($activeNav ?? '') === 'people' ? '#ffffff' : '#b8e0cc' ?>">People</a>
          <?php endif; ?>
          <?php if ($isOrgAdmin): ?>
            <a href="<?= url('/account/categories') ?>" style="color: <?= ($activeNav ?? '') === 'categories' ? '#ffffff' : '#b8e0cc' ?>">Categories</a>
          <?php endif; ?>
          <?php if ($canManageBranches): ?>
            <a href="<?= url('/account/branches') ?>" style="color: <?= ($activeNav ?? '') === 'branches' ? '#ffffff' : '#b8e0cc' ?>">Branches</a>
          <?php endif; ?>
          <?php if ($canViewCourses): ?>
            <a href="<?= url('/account/courses') ?>" style="color: <?= ($activeNav ?? '') === 'courses' ? '#ffffff' : '#b8e0cc' ?>">Courses</a>
          <?php endif; ?>
          <a href="<?= url('/account/logout') ?>" class="btn-danger" style="padding:0.4rem 0.75rem;">Sign Out</a>
        <?php else: ?>
          <a href="<?= url('/account/register') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Student register</a>
          <a href="<?= url('/account/register/trainer') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Trainer register</a>
          <a href="<?= url('/account/register/attachment-trainer') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Attachment trainer register</a>
          <a href="<?= url('/account/login') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Sign in</a>
          <a href="<?= url('/admin/login') ?>" class="btn-danger" style="padding:0.4rem 0.75rem;">Super Admin</a>
        <?php endif; ?>
        <a href="<?= url('/') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Home</a>
      </nav>
    </div>
  </header>
  <?php endif; ?>

  <main class="flex-1 w-full <?= $loggedInUser ? 'workspace-content account-main' : 'max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14 account-main' ?>">
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash-error"><?= e($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
