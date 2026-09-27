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
$isStudent = $isStudent ?? (($loggedInUser['role_slug'] ?? '') === 'student');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title><?= e($pageTitle ?? 'My Account') ?> | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app-shell.css') ?>">
</head>
<body class="antialiased h-dvh overflow-hidden flex flex-col srms-theme">
  <?php if ($loggedInUser): ?>
  <div class="flex-1 flex min-h-0">
    <div class="srms-sidebar-backdrop" id="srmsSidebarBackdrop"></div>
    <aside class="srms-sidebar" id="srmsSidebar">
      <div class="srms-brand-header">
        <button type="button" class="srms-sidebar-close" id="srmsSidebarClose" aria-label="Close menu"><?= icon('x') ?></button>
        <div class="srms-brand-icon"><?= icon('graduation', 'h-7 w-7') ?></div>
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
        <a href="<?= url('/account/dashboard') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'is-active' : '' ?>">
          <?= icon('dashboard') ?> Dashboard
        </a>
        <?php if ($navPortal): ?>
          <?php foreach (\App\Core\AccountNav::orderedItems($navPortal) as $navId => [$navIcon, $navLabel, $navHref]): ?>
            <a href="<?= url($navHref) ?>" class="srms-nav-link <?= ($activeNav ?? '') === $navId ? 'is-active' : '' ?>">
              <?= icon($navIcon) ?> <?= e($navLabel) ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <?php if ($canViewCourses): ?>
            <a href="<?= url('/account/courses') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'courses' ? 'is-active' : '' ?>">
              <?= icon('book') ?> <?= $isStudent ? 'My courses' : 'Courses' ?>
            </a>
          <?php endif; ?>
          <?php if (($currentUser['role_slug'] ?? '') === 'trainer'): ?>
            <a href="<?= url('/account/wallet') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'wallet' ? 'is-active' : '' ?>">
              <?= icon('wallet') ?> Earnings
            </a>
          <?php endif; ?>
          <?php if ($isBranchAdmin): ?>
            <a href="<?= url('/account/branch-admin') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'branch_admin' ? 'is-active' : '' ?>">
              <?= icon('graduation') ?> Attachees
            </a>
          <?php endif; ?>
          <?php if ($canManageUsers): ?>
            <a href="<?= url('/account/people') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'people' ? 'is-active' : '' ?>">
              <?= icon('users') ?> People
            </a>
          <?php endif; ?>
          <?php if ($canManageBranches): ?>
            <a href="<?= url('/account/branches') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'branches' ? 'is-active' : '' ?>">
              <?= icon('map-pin') ?> Branches
            </a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?= url('/account/profile') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'profile' ? 'is-active' : '' ?>">
          <?= icon('user') ?> Profile
        </a>
        <a href="<?= url('/account/logout') ?>" class="srms-nav-link mt-auto text-red-500 hover:text-red-600 hover:bg-red-50">
          <?= icon('logout') ?> Logout
        </a>
      </nav>
    </aside>
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
      <header class="srms-topbar">
        <div class="srms-topbar-title">
          <button type="button" class="srms-menu-toggle" id="srmsMenuToggle" aria-label="Open menu"><?= icon('menu', 'h-6 w-6') ?></button>
          <span><?= e($pageTitle ?? 'Dashboard') ?></span>
        </div>
        <div class="srms-topbar-right">
          <div class="srms-search">
            <span class="text-neutral-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="text" placeholder="Search anything...">
          </div>
          <?php
            $pendingRequestCount = (int) ($pendingRequestCount ?? 0);
            $bellHref = $isOrgAdmin ? '/account/trainer-requests' : ($isBranchAdmin ? '/account/branch-admin' : ($isCourseBranchAdmin ? '/account/course-branch-admin' : ($isAttachmentProvider ? '/account/people' : '/account/dashboard')));
          ?>
          <a href="<?= url($bellHref) ?>" class="relative cursor-pointer no-underline text-neutral-600" aria-label="Pending requests">
            <?= icon('bell', 'h-6 w-6') ?>
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
      <?php
        // Phone tab bar: Dashboard, the first three menu items, and More (the full menu).
        $tabShort = [
            'courses' => 'Courses', 'certificate' => 'Certificates', 'wallet' => 'Wallet',
            'course_organisations' => 'Organisations', 'attachment_providers' => 'Attachment',
            'trainer_requests' => 'Requests', 'organisation' => 'Organisation', 'people' => 'People',
            'categories' => 'Categories', 'branches' => 'Branches', 'attachment_audience' => 'Appear',
            'course_branch_admin' => 'Requests', 'branch_admin' => 'Attachees',
        ];
        $tabItems = [['dashboard', 'dashboard', 'Home', '/account/dashboard']];
        if ($navPortal) {
            foreach (\App\Core\AccountNav::orderedItems($navPortal) as $navId => [$navIcon, $navLabel, $navHref]) {
                $tabItems[] = [$navId, $navIcon, $tabShort[$navId] ?? $navLabel, $navHref];
            }
        } else {
            if ($canViewCourses) { $tabItems[] = ['courses', 'book', 'Courses', '/account/courses']; }
            if (($currentUser['role_slug'] ?? '') === 'trainer') { $tabItems[] = ['wallet', 'wallet', 'Earnings', '/account/wallet']; }
            if ($isBranchAdmin) { $tabItems[] = ['branch_admin', 'graduation', 'Attachees', '/account/branch-admin']; }
            if ($canManageUsers) { $tabItems[] = ['people', 'users', 'People', '/account/people']; }
        }
        $tabItems = array_slice($tabItems, 0, 4);
      ?>
      <nav class="app-tabbar" aria-label="Main">
        <?php foreach ($tabItems as [$tabId, $tabIcon, $tabLabel, $tabHref]):
          $isHere = ($activeNav ?? '') === $tabId;
        ?>
          <a href="<?= url($tabHref) ?>" class="app-tab <?= $isHere ? 'is-active' : '' ?>" <?= $isHere ? 'aria-current="page"' : '' ?>>
            <?= icon($tabIcon, 'h-6 w-6') ?><span><?= e($tabLabel) ?></span>
          </a>
        <?php endforeach; ?>
        <button type="button" class="app-tab" onclick="document.getElementById('srmsMenuToggle').click()" aria-label="More — open the full menu">
          <?= icon('more', 'h-6 w-6') ?><span>More</span>
        </button>
      </nav>
      <div class="app-progress" id="appProgress" aria-hidden="true"></div>
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
      <nav class="hidden md:flex items-center gap-5 text-sm font-semibold text-gray-700">
        <a href="<?= url('/') ?>" class="hover:text-red-600 transition">Home</a>
        <a href="<?= url('/courses') ?>" class="hover:text-red-600 transition">Courses</a>
        <a href="<?= url('/about') ?>" class="hover:text-red-600 transition">About Us</a>
        <a href="<?= url('/account/login') ?>" class="hover:text-red-600 transition">Sign in</a>
        <div class="ujuzi-dropdown">
          <button class="ujuzi-nav-btn" type="button" id="layoutSignupBtn">Sign up ▾</button>
          <div class="ujuzi-dropdown-menu" id="layoutSignupMenu">
            <div class="menu-section-label">Course Portals</div>
            <a href="<?= url('/account/login/organisation-admin') ?>"><span class="menu-icon" style="background:#fef2f2;color:#dc2626;">🏢</span><span>Organisation (Course Provider)</span></a>
            <a href="<?= url('/account/login/course-branch-admin') ?>"><span class="menu-icon" style="background:#fff7ed;color:#ea580c;">🏬</span><span>Branch Admin (Course Org)</span></a>
            <a href="<?= url('/account/register') ?>"><span class="menu-icon" style="background:#f0fdf4;color:#16a34a;">🎓</span><span>Student</span></a>
            <a href="<?= url('/account/register/trainer') ?>"><span class="menu-icon" style="background:#f0fdf4;color:#15803d;">👨‍🏫</span><span>Tutor / Teacher</span></a>
            <div class="menu-section-label">Attachment Portals</div>
            <a href="<?= url('/account/register/attachment-trainer') ?>"><span class="menu-icon" style="background:#faf5ff;color:#7c3aed;">🤝</span><span>Organisation (Attachment Provider)</span></a>
            <a href="<?= url('/account/login/branch-admin') ?>"><span class="menu-icon" style="background:#f1f5f9;color:#334155;">📍</span><span>Branch (Attachment Org)</span></a>
          </div>
        </div>
      </nav>
      <div class="flex items-center gap-4">
        <a href="<?= url('/account/register') ?>" class="srms-btn-red hidden sm:inline-flex">Enroll Now</a>
        <button class="md:hidden text-gray-700 text-2xl" id="publicMenuToggle">☰</button>
      </div>
    </div>
  </header>
  
  <!-- Public Mobile Menu -->
  <div id="publicMobileMenu" class="hidden md:hidden bg-white border-b border-gray-100 shadow-lg px-4 py-4 space-y-2 absolute w-full z-40">
    <a href="<?= url('/') ?>" class="block font-semibold text-gray-700 hover:text-red-600 py-1">Home</a>
    <a href="<?= url('/courses') ?>" class="block font-semibold text-gray-700 hover:text-red-600 py-1">Courses</a>
    <a href="<?= url('/about') ?>" class="block font-semibold text-gray-700 hover:text-red-600 py-1">About Us</a>
    <a href="<?= url('/account/login') ?>" class="block font-semibold text-gray-700 hover:text-red-600 py-1">Sign in</a>
    <div class="border-t border-gray-100 pt-3 mt-2">
      <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Sign up as:</p>
      <a href="<?= url('/account/login/organisation-admin') ?>" class="block text-sm font-semibold text-gray-600 hover:text-red-600 py-1">🏢 Organisation (Course Provider)</a>
      <a href="<?= url('/account/login/course-branch-admin') ?>" class="block text-sm font-semibold text-gray-600 hover:text-red-600 py-1">🏬 Branch Admin (Course Org)</a>
      <a href="<?= url('/account/register') ?>" class="block text-sm font-semibold text-gray-600 hover:text-green-600 py-1">🎓 Student</a>
      <a href="<?= url('/account/register/trainer') ?>" class="block text-sm font-semibold text-gray-600 hover:text-green-600 py-1">👨‍🏫 Tutor / Teacher</a>
      <a href="<?= url('/account/register/attachment-trainer') ?>" class="block text-sm font-semibold text-gray-600 hover:text-green-600 py-1">🤝 Organisation (Attachment Provider)</a>
      <a href="<?= url('/account/login/branch-admin') ?>" class="block text-sm font-semibold text-gray-600 hover:text-green-600 py-1">📍 Branch (Attachment Org)</a>
    </div>
    <a href="<?= url('/account/register') ?>" class="block srms-btn-red w-full text-center mt-4">Enroll Now</a>
  </div>
  
  <script>
    const publicMenuToggle = document.getElementById('publicMenuToggle');
    const publicMobileMenu = document.getElementById('publicMobileMenu');
    if (publicMenuToggle && publicMobileMenu) {
      publicMenuToggle.addEventListener('click', function() {
        publicMobileMenu.classList.toggle('hidden');
      });
    }
    (function() {
      var db = document.getElementById('layoutSignupBtn'), dm = document.getElementById('layoutSignupMenu');
      if (db && dm) {
        db.addEventListener('click', function(e){ e.stopPropagation(); dm.style.display = dm.style.display==='block'?'none':'block'; });
        document.addEventListener('click', function(){ if(dm) dm.style.display='none'; });
      }
    })();
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
