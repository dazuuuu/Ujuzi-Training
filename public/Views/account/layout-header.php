<?php
/**
 * Shared LMS user-account shell. Include after setting $pageTitle and optional $activeNav.
 */
use App\Core\UserSession;
$loggedInUser = $currentUser ?? UserSession::current();
$canManageUsers = $canManageUsers ?? false;
$isOrgAdmin = $isOrgAdmin ?? false;
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

  <header class="w-full py-3 sm:py-4 text-white" style="background:var(--ke-black)">
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
      <nav class="flex flex-wrap items-center justify-end gap-3 text-xs font-bold uppercase tracking-wider">
        <?php if ($loggedInUser): ?>
          <a href="<?= url('/account/dashboard') ?>" class="<?= ($activeNav ?? '') === 'dashboard' ? 'text-white' : '' ?>" style="color: <?= ($activeNav ?? '') === 'dashboard' ? '#ffffff' : '#b8e0cc' ?>">Dashboard</a>
          <a href="<?= url('/account/profile') ?>" style="color: <?= ($activeNav ?? '') === 'profile' ? '#ffffff' : '#b8e0cc' ?>">Profile</a>
          <?php if ($canManageUsers): ?>
            <a href="<?= url('/account/people') ?>" style="color: <?= ($activeNav ?? '') === 'people' ? '#ffffff' : '#b8e0cc' ?>">People</a>
          <?php endif; ?>
          <?php if ($isOrgAdmin): ?>
            <a href="<?= url('/account/categories') ?>" style="color: <?= ($activeNav ?? '') === 'categories' ? '#ffffff' : '#b8e0cc' ?>">Categories</a>
          <?php endif; ?>
          <?php if ($canViewCourses): ?>
            <a href="<?= url('/account/courses') ?>" style="color: <?= ($activeNav ?? '') === 'courses' ? '#ffffff' : '#b8e0cc' ?>">Courses</a>
          <?php endif; ?>
          <a href="<?= url('/account/logout') ?>" class="btn-danger" style="padding:0.4rem 0.75rem;">Sign Out</a>
        <?php else: ?>
          <a href="<?= url('/account/register') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Student register</a>
          <a href="<?= url('/account/register/trainer') ?>" class="btn-secondary" style="padding:0.4rem 0.75rem;">Trainer register</a>
          <a href="<?= url('/account/login') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Sign in</a>
          <a href="<?= url('/admin/login') ?>" class="btn-danger" style="padding:0.4rem 0.75rem;">Super Admin</a>
        <?php endif; ?>
        <a href="<?= url('/') ?>" class="btn-primary" style="padding:0.4rem 0.75rem;">Home</a>
      </nav>
    </div>
  </header>

  <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash-error"><?= e($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
