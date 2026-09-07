<?php
/** Requires $error, $notice, $email in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-md mx-auto text-center">
  <span class="text-xs font-bold text-black uppercase tracking-widest block mb-1">Verify It's You</span>
  <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Enter Your Login Code</h1>
  <p class="text-sm text-neutral-500 mt-2 mb-8">
    We sent a 6-digit code to <strong class="text-neutral-800"><?= e($email) ?></strong>.
  </p>

  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 text-left">
    <?php if ($error): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($notice): ?>
      <div class="bg-neutral-50 border border-neutral-300 text-black text-sm rounded-lg p-3 mb-4"><?= e($notice) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/account/verify') ?>" class="space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">6-Digit Code</label>
        <input type="text" name="code" required autofocus maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-3 text-center text-2xl font-mono tracking-[0.5em] focus:outline-none focus:border-black" />
      </div>
      <button type="submit" class="btn-primary btn-block">Verify &amp; Sign In</button>
    </form>

    <form method="post" action="<?= url('/account/verify') ?>" class="mt-3">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="resend" />
      <button type="submit" class="btn-secondary btn-block">Resend code</button>
    </form>
  </div>

  <p class="text-[11px] text-neutral-400 mt-4"><a href="<?= url($loginPath ?? '/account/login') ?>" class="hover:text-neutral-700">&larr; Use a different email or phone number</a></p>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
