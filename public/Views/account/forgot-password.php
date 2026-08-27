<?php
/** Requires $error, $email, $role, $loginPath in scope. */
require __DIR__ . '/layout-header.php';
$loginPath = $loginPath ?? '/account/login';
?>

<div class="max-w-md mx-auto">
  <div class="text-center mb-8">
    <span class="text-xs font-bold uppercase tracking-widest block mb-1" style="color:var(--ke-green)">Reset password</span>
    <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Get an email code</h1>
    <p class="text-sm text-neutral-500 mt-2">We send a 6-digit OTP to your email using the SMTP credentials saved in Super Admin → Settings.</p>
  </div>

  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
    <?php if (!empty($error)): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= url('/account/forgot-password') ?>" class="space-y-4">
      <?= csrfField() ?>
      <input type="hidden" name="role" value="<?= e($role ?? '') ?>" />
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Email address</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>" autocomplete="email" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
      </div>
      <button type="submit" class="btn-primary btn-block">Send code</button>
    </form>
  </div>
  <p class="text-[11px] text-neutral-400 mt-4 text-center"><a href="<?= url($loginPath) ?>" class="hover:text-neutral-700">&larr; Back to sign in</a></p>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
