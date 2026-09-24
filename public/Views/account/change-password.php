<?php
$forced = !empty($forced);
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-md mx-auto space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Set your password</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">
      <?= $forced
        ? 'You signed in with a default password. Set your own password to continue.'
        : 'Choose a new password for your account.' ?>
    </p>
  </section>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <form method="post" action="<?= url('/account/change-password') ?>" class="space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">New password</label>
        <input type="password" name="password" required minlength="8" class="mt-1 block w-full rounded border border-neutral-300 p-2 text-sm" autocomplete="new-password" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Confirm new password</label>
        <input type="password" name="password_confirm" required minlength="8" class="mt-1 block w-full rounded border border-neutral-300 p-2 text-sm" autocomplete="new-password" />
      </div>
      <button type="submit" class="btn-primary w-full">Save password</button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
