<?php
$organisationName = trim((string) ($currentUser['organisation_name'] ?? ''));
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation approvals</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Trainer Requests</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
        <?= e($organisationName !== '' ? $organisationName : 'No organisation assigned') ?> · approve tutors, trainers, or teachers who selected this organisation.
      </p>
    </div>
    <a href="<?= url('/account/courses') ?>" class="btn-secondary">Courses</a>
  </section>

  <?php if ($organisationName === ''): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      This organisation admin account is not attached to an organisation yet. Super Admin must assign it before trainer approvals can appear.
    </section>
  <?php else: ?>
    <?php require __DIR__ . '/../partials/trainer-requests.php'; ?>
    <?php require __DIR__ . '/../partials/student-requests.php'; ?>
    <?php
      $categoryLookup = static fn(int $orgId): array => $categories;
      $categoriesAction = '/account/trainer-requests/';
      require __DIR__ . '/../partials/approved-students.php';
    ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
