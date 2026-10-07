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
  <?= \App\Services\SiteTheme::head('portal') ?>
</head>
<body class="antialiased h-dvh overflow-hidden flex flex-col srms-theme">
  <?php if ($loggedInUser): ?>
  <div class="flex-1 flex min-h-0">
    <div class="srms-sidebar-backdrop" id="srmsSidebarBackdrop"></div>
    <aside class="srms-sidebar" id="srmsSidebar">
      <div class="srms-brand-header">
        <button type="button" class="srms-sidebar-close" id="srmsSidebarClose" aria-label="Close menu"><?= icon('x') ?></button>
        <div class="srms-brand-icon<?= storeLogoPath() ? ' has-logo' : '' ?>"><?= storeLogoPath() ? storeLogoHtml('srms-brand-logo') : icon('graduation', 'h-7 w-7') ?></div>
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
            <a href="<?= url('/account/reports') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'reports' ? 'is-active' : '' ?>">
              <?= icon('chart') ?> Reports
            </a>
            <a href="<?= url('/account/student-lookup') ?>" class="srms-nav-link <?= ($activeNav ?? '') === 'verify_certificate' ? 'is-active' : '' ?>">
              <?= icon('search') ?> Student lookup
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
          <button type="button" class="ss-trigger" data-ss-open aria-label="Search courses, organisations and pages" aria-haspopup="dialog">
            <?= icon('search', 'h-5 w-5') ?><span class="ss-trigger-text">Search courses, attachment…</span><kbd class="ss-kbd">Ctrl K</kbd>
          </button>
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
          <details class="user-menu">
            <summary class="srms-user-profile" aria-label="Account menu">
              <span class="srms-user-info text-right hidden sm:block">
                <span class="srms-user-name block"><?= e(userDisplayName($loggedInUser)) ?></span>
                <span class="srms-user-role block"><?= e($loggedInUser['role_name'] ?? '') ?></span>
              </span>
              <?php if (!empty($loggedInUser['photo_path'])): ?>
                <img src="<?= e(imageUrl($loggedInUser['photo_path'])) ?>" alt="" class="srms-avatar object-cover">
              <?php else: ?>
                <span class="srms-avatar"><?= e(strtoupper(substr((string) ($loggedInUser['first_name'] ?: $loggedInUser['email'] ?: 'U'), 0, 1))) ?></span>
              <?php endif; ?>
              <span class="hidden text-neutral-500 sm:block"><?= icon('chevron-down', 'h-4 w-4') ?></span>
            </summary>
            <div class="user-menu-panel" role="menu">
              <div class="user-menu-head">
                <p class="truncate text-sm font-black text-gray-800"><?= e(userDisplayName($loggedInUser)) ?></p>
                <p class="truncate text-xs font-semibold text-neutral-500"><?= e($loggedInUser['email'] ?? '') ?></p>
                <?php if (!empty($loggedInUser['registration_number'])): ?>
                  <p class="mt-1 text-xs font-black tracking-wide" style="color:var(--ke-green)"><?= e($loggedInUser['registration_number']) ?></p>
                <?php endif; ?>
              </div>
              <a href="<?= url('/account/profile') ?>" role="menuitem"><?= icon('user', 'h-4 w-4') ?> Profile</a>
              <a href="<?= url('/account/change-password') ?>" role="menuitem"><?= icon('key', 'h-4 w-4') ?> Change password</a>
              <a href="<?= url('/account/logout') ?>" role="menuitem" class="is-danger"><?= icon('logout', 'h-4 w-4') ?> Logout</a>
            </div>
          </details>
        </div>
      </header>
      <?php require __DIR__ . '/partials/smart-search.php'; ?>
      <?php
        // Phone tab bar: Dashboard, the first three menu items, and More (the full menu).
        $tabShort = [
            'courses' => 'Courses', 'certificate' => 'Certificates', 'wallet' => 'Wallet',
            'course_organisations' => 'Organisations', 'attachment_providers' => 'Attachment',
            'trainer_requests' => 'Requests', 'organisation' => 'Organisation', 'people' => 'People',
            'categories' => 'Categories', 'branches' => 'Branches', 'attachment_audience' => 'Appear',
            'course_branch_admin' => 'Requests', 'branch_admin' => 'Attachees',
            'attachment_partners' => 'Partners', 'verify_certificate' => 'Lookup',
        ];
        $tabItems = [['dashboard', 'dashboard', 'Home', '/account/dashboard']];
        if ($navPortal) {
            foreach (\App\Core\AccountNav::orderedItems($navPortal) as $navId => [$navIcon, $navLabel, $navHref]) {
                $tabItems[] = [$navId, $navIcon, $tabShort[$navId] ?? $navLabel, $navHref];
            }
        } else {
            if ($canViewCourses) { $tabItems[] = ['courses', 'book', 'Courses', '/account/courses']; }
            if (($currentUser['role_slug'] ?? '') === 'trainer') { $tabItems[] = ['wallet', 'wallet', 'Earnings', '/account/wallet']; }
            if ($isBranchAdmin) { $tabItems[] = ['branch_admin', 'graduation', 'Attachees', '/account/branch-admin']; $tabItems[] = ['verify_certificate', 'search', 'Lookup', '/account/student-lookup']; }
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
  <?php // Signed-out pages share the public navbar Super Admin edits under Public pages. ?>
  <div class="shrink-0"><?php require __DIR__ . '/../lms/partials/public-nav.php'; ?></div>
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
