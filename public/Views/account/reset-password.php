<?php
/** Requires $error, $loginPath in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-md mx-auto">
  <div class="text-center mb-8">
    <span class="text-xs font-bold uppercase tracking-widest block mb-1" style="color:var(--ke-green)">Reset password</span>
    <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Choose a new password</h1>
    <p class="text-sm text-neutral-500 mt-2">Use at least 8 characters. You will sign in with this password next time.</p>
  </div>

  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
    <?php if (!empty($error)): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= url('/account/forgot-password/new') ?>" class="space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">New password</label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Confirm password</label>
        <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm" />
      </div>
      <button type="submit" class="btn-primary btn-block">Save password</button>
    </form>
  </div>
  <p class="text-[11px] text-neutral-400 mt-4 text-center"><a href="<?= url($loginPath ?? '/account/login') ?>" class="hover:text-neutral-700">&larr; Back to sign in</a></p>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
