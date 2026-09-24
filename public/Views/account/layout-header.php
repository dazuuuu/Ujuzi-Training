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
$isBranchAdmin = $isBranchAdmin ?? false;
$isCourseBranchAdmin = $isCourseBranchAdmin ?? false;
$isAttachmentProvider = $isAttachmentProvider ?? false;
$isStudent = $isStudent ?? false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title><?= e($pageTitle ?? 'My Account') ?> | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
</head>
<body class="antialiased h-dvh overflow-hidden flex flex-col srms-theme">
  <?php if ($loggedInUser): ?>
  <div class="flex-1 flex min-h-0">
    <div class="srms-sidebar-backdrop" id="srmsSidebarBackdrop"></div>
    <aside class="srms-sidebar" id="srmsSidebar">
      <div class="srms-brand-header">
        <button type="button" class="srms-sidebar-close" id="srmsSidebarClose" aria-label="Close menu">✕</button>
        <div class="srms-brand-icon">🎓</div>
        <div class="srms-brand-title"><?= e(appName()) ?></div>
        <div class="srms-brand-subtitle"><?= e($loggedInUser['role_name'] ?? 'Account') ?></div>
      </div>
      <?php
        $navPortal = $isStudent ? \App\Core\AccountNav::PORTAL_STUDENT
          : ($isOrgAdmin ? \App\Core\AccountNav::PORTAL_ORGANISATION_ADMIN
          : ($isAttachmentProvider ? \App\Core\AccountNav::PORTAL_ATTACHMENT_TRAINER
          : ($isCourseBranchAdmin ? \App\Core\AccountNav::PORTAL_COURSE_BRANCH_ADMIN : null)));
      ?>
      <nav class="srms-nav">
        <a href="<?= url('/account') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'landing' ? 'is-active' : '' ?>">
          <span class="text-lg">🏠</span> Home
        </a>
        <a href="<?= url('/account/dashboard') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'is-active' : '' ?>">
          <span class="text-lg">📊</span> Dashboard
        </a>
        <?php if ($navPortal): ?>
          <?php foreach (\App\Core\AccountNav::orderedItems($navPortal) as $navId => [$navIcon, $navLabel, $navHref]): ?>
            <a href="<?= url($navHref) ?>" class="srms-nav-link <?= ($activeNav ?? '') === $navId ? 'is-active' : '' ?>">
              <span class="text-lg"><?= $navIcon ?></span> <?= e($navLabel) ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <?php if ($canViewCourses): ?>
            <a href="<?= url('/account/courses') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'courses' ? 'is-active' : '' ?>">
              <span class="text-lg">📚</span> Courses
            </a>
          <?php endif; ?>
          <?php if ($isBranchAdmin): ?>
            <a href="<?= url('/account/branch-admin') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'branch_admin' ? 'is-active' : '' ?>">
              <span class="text-lg">🎓</span> Attachees
            </a>
          <?php endif; ?>
          <?php if ($canManageUsers): ?>
            <a href="<?= url('/account/people') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'people' ? 'is-active' : '' ?>">
              <span class="text-lg">👥</span> People
            </a>
          <?php endif; ?>
          <?php if ($canManageBranches): ?>
            <a href="<?= url('/account/branches') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'branches' ? 'is-active' : '' ?>">
              <span class="text-lg">📍</span> Branches
            </a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?= url('/account/profile') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'profile' ? 'is-active' : '' ?>">
          <span class="text-lg">👤</span> Profile
        </a>
        <a href="<?= url('/account/logout') ?>" class="srms-nav-link mt-auto text-red-500 hover:text-red-600 hover:bg-red-50">
          <span class="text-lg">🚪</span> Logout
        </a>
      </nav>
    </aside>
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
      <header class="srms-topbar">
        <div class="srms-topbar-title">
          <button type="button" class="srms-menu-toggle" id="srmsMenuToggle" aria-label="Toggle menu">☰</button>
          <span><?= e($pageTitle ?? 'Dashboard') ?></span>
        </div>
        <div class="srms-topbar-right">
          <div class="srms-search">
            <span>🔍</span>
            <input type="text" placeholder="Search anything...">
          </div>
          <?php
            $pendingRequestCount = (int) ($pendingRequestCount ?? 0);
            $bellHref = $isOrgAdmin ? '/account/trainer-requests' : ($isBranchAdmin ? '/account/branch-admin' : ($isCourseBranchAdmin ? '/account/course-branch-admin' : ($isAttachmentProvider ? '/account/people' : '/account/dashboard')));
          ?>
          <a href="<?= url($bellHref) ?>" class="relative cursor-pointer text-xl no-underline" aria-label="Pending requests">
            🔔
            <?php if ($pendingRequestCount > 0): ?>
              <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full"><?= $pendingRequestCount > 9 ? '9+' : $pendingRequestCount ?></span>
            <?php endif; ?>
          </a>
          <div class="srms-user-profile">
            <div class="srms-user-info text-right hidden sm:block">
              <div class="srms-user-name">Hello, <?= e(userDisplayName($loggedInUser)) ?></div>
              <div class="srms-user-role"><?= e($loggedInUser['role_name'] ?? '') ?></div>
            </div>
            <div class="srms-avatar">
              <?= e(strtoupper(substr((string) ($loggedInUser['first_name'] ?? $loggedInUser['email'] ?? 'U'), 0, 1))) ?>
            </div>
          </div>
        </div>
      </header>
  <?php else: ?>
  <header class="w-full py-4 bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 group">
        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-600 text-white shadow-sm shrink-0">
          <?= storeLogoHtml('w-full h-full object-contain rounded-lg', 'w-4 h-4 text-white') ?>
        </div>
        <div class="flex flex-col text-left leading-none">
          <span class="font-serif-heading text-xl font-bold tracking-tight text-gray-900"><?= e(appName()) ?>.</span>
        </div>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-700">
        <a href="<?= url('/') ?>" class="hover:text-green-600 transition">Home</a>
        <a href="<?= url('/courses') ?>" class="hover:text-green-600 transition">Courses</a>
        <a href="<?= url('/account/login') ?>" class="hover:text-green-600 transition">Sign in</a>
        <a href="<?= url('/account/register/choose') ?>" class="hover:text-green-600 transition">Sign up</a>
      </nav>
      <div class="flex items-center gap-4">
        <a href="<?= url('/account/register/choose') ?>" class="srms-btn-red hidden sm:inline-flex">Enroll Now</a>
        <button class="md:hidden text-gray-700 text-2xl" id="publicMenuToggle">☰</button>
      </div>
    </div>
  </header>
  
  <!-- Public Mobile Menu -->
  <div id="publicMobileMenu" class="hidden md:hidden bg-white border-b border-gray-100 shadow-lg px-4 py-4 space-y-3 absolute w-full z-40">
    <a href="<?= url('/') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Home</a>
    <a href="<?= url('/courses') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Courses</a>
    <a href="<?= url('/account/login') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Sign in</a>
    <a href="<?= url('/account/register/choose') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Sign up</a>
    <a href="<?= url('/account/register/choose') ?>" class="block srms-btn-red w-full text-center mt-4">Enroll Now</a>
  </div>
  
  <script>
    const publicMenuToggle = document.getElementById('publicMenuToggle');
    const publicMobileMenu = document.getElementById('publicMobileMenu');
    if (publicMenuToggle && publicMobileMenu) {
      publicMenuToggle.addEventListener('click', function() {
        publicMobileMenu.classList.toggle('hidden');
      });
    }
  </script>
  <?php endif; ?>

  <main class="srms-main overflow-y-auto overflow-x-hidden <?= $loggedInUser ? 'flex-1' : 'max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14 account-main flex-1' ?>">
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="flash-success mt-4"><?= e($_SESSION['flash_success']) ?></div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success_html'])): ?>
      <div class="flash-success mt-4"><?= $_SESSION['flash_success_html'] ?></div>
      <?php unset($_SESSION['flash_success_html']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash-error mt-4"><?= e($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
