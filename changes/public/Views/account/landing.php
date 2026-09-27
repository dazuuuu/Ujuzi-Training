<?php
require __DIR__ . '/layout-header.php';
?>
<div class="account-landing">
  <section class="landing-intro">
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= e($currentUser['role_name'] ?? 'Account') ?></p>
    <h1 class="mt-2 text-3xl font-bold">Welcome, <?= e($currentUser['first_name'] ?: userDisplayName($currentUser)) ?></h1>
    <p class="mt-2 text-sm" style="color:var(--ke-muted)">Choose where you want to continue.</p>
  </section>
  <section class="landing-grid" aria-label="Account pages">
    <a href="<?= url('/account/dashboard') ?>" class="landing-card landing-card-green"><span class="landing-card-kicker">Workspace</span><strong>Dashboard</strong><span>View your activity, courses, people, requests, and role tools.</span></a>
    <a href="<?= url('/account/profile') ?>" class="landing-card landing-card-red"><span class="landing-card-kicker">Personal information</span><strong>Profile</strong><span>Review and re-edit your registration details and sign-in credentials.</span></a>
  </section>
</div>
<?php require __DIR__ . '/layout-footer.php'; ?>
