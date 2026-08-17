<?php
/** Broken / expired / used organisation-admin invite. Requires $message. */
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-md mx-auto text-center">
  <h1 class="font-serif-heading text-3xl font-bold">This link is no longer valid</h1>
  <p class="mt-4 text-sm font-medium" style="color:var(--ke-muted)"><?= e($message) ?></p>
  <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
    <a href="<?= url('/account/login/organisation-admin') ?>" class="btn-primary">Organisation admin sign in</a>
    <a href="<?= url('/') ?>" class="btn-secondary">Home</a>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
