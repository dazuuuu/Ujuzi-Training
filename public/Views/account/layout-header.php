<?php
/**
 * Shared LMS user-account shell. Include after setting $pageTitle and optional $activeNav.
 */
use App\Core\UserSession;
$loggedInUser = $currentUser ?? UserSession::current();
$canManageUsers = $canManageUsers ?? false;
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
<body class="bg-white text-[#1a1a1a] antialiased min-h-screen flex flex-col">

  <header class="w-full bg-black py-3 sm:py-4 border-b border-neutral-800 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 group">
        <div class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center bg-[#0a0a0a] text-white rounded-md border border-neutral-300 shrink-0">
          <?= storeLogoHtml('w-full h-full object-contain rounded-md', 'w-4 h-4 text-white') ?>
        </div>
        <div class="flex flex-col text-left leading-none">
          <span class="font-serif-heading text-base sm:text-lg font-extrabold tracking-[0.18em] text-white uppercase"><?= e(appName()) ?></span>
          <span class="text-[8px] tracking-[0.3em] text-neutral-300 font-sans font-semibold uppercase mt-0.5">
            <?= $loggedInUser ? e($loggedInUser['role_name'] ?? 'Account') : 'Sign in' ?>
          </span>
        </div>
      </a>
      <nav class="flex items-center gap-4 text-xs font-bold uppercase tracking-wider text-neutral-300">
        <?php if ($loggedInUser): ?>
          <a href="<?= url('/account/dashboard') ?>" class="<?= ($activeNav ?? '') === 'dashboard' ? 'text-white' : 'hover:text-white' ?>">Dashboard</a>
          <a href="<?= url('/account/profile') ?>" class="<?= ($activeNav ?? '') === 'profile' ? 'text-white' : 'hover:text-white' ?>">Profile</a>
          <?php if ($canManageUsers): ?>
            <a href="<?= url('/account/people') ?>" class="<?= ($activeNav ?? '') === 'people' ? 'text-white' : 'hover:text-white' ?>">People</a>
          <?php endif; ?>
          <a href="<?= url('/account/logout') ?>" class="hover:text-white">Sign Out</a>
        <?php else: ?>
          <a href="<?= url('/admin/login') ?>" class="hover:text-white">Super Admin</a>
        <?php endif; ?>
        <a href="<?= url('/') ?>" class="hover:text-white">Home &rarr;</a>
      </nav>
    </div>
  </header>

  <main class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
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
