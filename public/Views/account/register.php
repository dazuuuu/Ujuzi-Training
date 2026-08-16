<?php
/** Student or organisation-admin registration. Requires $heading, $blurb, $action, $error, $email. */
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-md mx-auto">
  <div class="text-center mb-8">
    <span class="text-xs font-bold uppercase tracking-widest block mb-1" style="color:var(--ke-green)"><?= ($mode ?? '') === 'student' ? 'Student registration' : 'Organisation admin' ?></span>
    <h1 class="font-serif-heading text-3xl font-bold"><?= e($heading) ?></h1>
    <p class="text-sm mt-2 font-medium" style="color:var(--ke-muted)"><?= e($blurb) ?></p>
  </div>

  <div class="bg-white border rounded-xl shadow-sm p-6" style="border-color:var(--ke-line)">
    <?php if ($error): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Email</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>" autocomplete="email" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Password</label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Confirm password</label>
        <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm" />
      </div>
      <button type="submit" class="btn-primary btn-block">Create account</button>
    </form>
  </div>

  <p class="mt-6 text-center text-sm font-semibold" style="color:var(--ke-muted)">
    Already registered? <a href="<?= url('/account/login') ?>" class="font-bold" style="color:var(--ke-green)">Sign in</a>
  </p>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
