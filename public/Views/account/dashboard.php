<?php
/**
 * Dashboard for every role except students (dashboard-student.php): small
 * cards for what needs doing, then the role's lists.
 * Requires $currentUser, $cards (DashboardController::roleCards), $needsProfile,
 * $audienceCount, $recentManaged, $canManageUsers and, for organisation
 * admins, $pendingTrainerRequests.
 */
require __DIR__ . '/layout-header.php';
$roleSlug = (string) ($currentUser['role_slug'] ?? '');
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<div class="space-y-6">
  <section class="min-w-0">
    <p class="text-xs font-semibold text-neutral-500"><?= e($greeting) ?>,</p>
    <h1 class="truncate text-2xl font-black text-gray-900"><?= e($currentUser['first_name'] ?: userDisplayName($currentUser)) ?></h1>
    <p class="truncate text-xs font-bold text-neutral-500"><?= e($currentUser['role_name'] ?? '') ?><?= !empty($currentUser['organisation_name']) ? ' · ' . e($currentUser['organisation_name']) : '' ?></p>
  </section>

  <?php if (!empty($needsProfile)): ?>
    <a href="<?= url('/account/profile') ?>" class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800 app-press">
      <span><strong class="font-black">Complete your profile</strong> — finish the registration details for your role.</span>
      <?= icon('arrow-right', 'h-4 w-4 shrink-0') ?>
    </a>
  <?php endif; ?>

  <?php if ($roleSlug === 'attachment_trainer' && ($audienceCount ?? null) === 0): ?>
    <a href="<?= url('/account/attachment-audience') ?>" class="flex items-center justify-between gap-3 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800 app-press">
      <span><strong class="font-black">Students can't see you yet.</strong> Ask organisations providing courses to take their students; they see you once approved.</span>
      <?= icon('arrow-right', 'h-4 w-4 shrink-0') ?>
    </a>
  <?php endif; ?>

  <?php if ($cards): ?>
    <section class="mini-grid" aria-label="Summary">
      <?php foreach ($cards as [$cardIcon, $cardLabel, $cardValue, $cardHref, $needsAttention]): ?>
        <a href="<?= url($cardHref) ?>" class="mini-card app-press">
          <span class="mini-card-icon" style="<?= $needsAttention ? 'background:#fef2f2;color:var(--ke-red)' : '' ?>"><?= icon($cardIcon) ?></span>
          <span class="min-w-0">
            <span class="mini-card-label"><?= e($cardLabel) ?></span>
            <span class="mini-card-value" style="<?= $needsAttention ? 'color:var(--ke-red)' : '' ?>"><?= e($cardValue) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($roleSlug === 'organisation_admin'): ?>
    <?php require __DIR__ . '/partials/trainer-requests.php'; ?>
  <?php endif; ?>

  <?php if ($roleSlug === 'trainer'): ?>
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-4" style="border-color:var(--ke-line)">
      <div class="min-w-0">
        <h2 class="text-sm font-black text-gray-800">Courses</h2>
        <p class="text-xs font-semibold text-neutral-500">Create a course under your organisation's categories, then add modules.</p>
      </div>
      <div class="flex gap-2">
        <a href="<?= url('/account/courses') ?>" class="btn-secondary">My courses</a>
        <?php if (!empty($canCreateCourses)): ?><a href="<?= url('/account/courses/create') ?>" class="btn-primary">Create course</a><?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($canManageUsers && !empty($recentManaged)): ?>
    <section class="space-y-2">
      <div class="section-head">
        <h2>People</h2>
        <a href="<?= url('/account/people') ?>">See all</a>
      </div>
      <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($recentManaged as $person): ?>
          <li>
            <a href="<?= url('/account/people/' . (int) $person['id']) ?>" class="mini-card app-press">
              <span class="mini-card-icon"><?= e(strtoupper(substr((string) ($person['first_name'] ?: $person['email'] ?: 'U'), 0, 1))) ?></span>
              <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-gray-800"><?= e(userDisplayName($person)) ?></span>
                <span class="block truncate text-[11px] font-semibold uppercase text-neutral-500"><?= e($person['role_name']) ?></span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
