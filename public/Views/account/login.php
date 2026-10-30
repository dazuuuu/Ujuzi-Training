<?php
/** Role-specific LMS login. Requires $error, $method, $old, $roleSlug, $roleMeta, $loginPath. */
use App\Core\LoginRoles;
require __DIR__ . '/layout-header.php';
$meta = $roleMeta ?? LoginRoles::meta($roleSlug ?? '');
$loginPath = $loginPath ?? '/account/login';
$registerPath = $registerPath ?? LoginRoles::registerPath((string) ($roleSlug ?? ''));
?>

<div class="flex items-center justify-center w-full min-h-[70vh]">
  <div class="srms-login-card w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-gray-100 relative z-10">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-green-50 text-green-700 mb-4 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <span class="text-xs font-bold uppercase tracking-widest block mb-1 text-red-600"><?= e($meta['badge'] ?? 'Sign in') ?></span>
      <h1 class="text-3xl font-extrabold text-gray-900"><?= e($meta['heading'] ?? 'Sign in to your dashboard') ?></h1>
      <p class="text-sm text-gray-500 mt-2">
        <?= e($meta['blurb'] ?? 'Use the email and password for this role.') ?>
      </p>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-50 text-red-700 p-4 rounded-lg text-sm font-semibold mb-6 flex items-start gap-3 border border-red-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url($loginPath) ?>" class="space-y-5 relative z-20">
      <?= csrfField() ?>
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1" for="login-id">Email or phone number</label>
        <input id="login-id" type="text" name="login" required value="<?= e($old['login'] ?? '') ?>" placeholder="you@example.com or 0712 345 678" autocomplete="username" data-phone-smart class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-gray-900" />
        <p class="mt-1 text-xs text-gray-500">The email or phone number on your registration form.</p>
      </div>
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-sm font-bold text-gray-700" for="login-password">Password</label>
          <a href="<?= url('/account/forgot-password?role=' . rawurlencode((string) ($roleSlug ?? ''))) ?>" class="text-xs font-semibold text-red-600 hover:text-blue-800 transition">Forgot password?</a>
        </div>
        <input id="login-password" type="password" name="password" autocomplete="current-password" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-gray-900" />
        <p class="mt-2 text-xs text-gray-500">Leave blank only if an admin created your account and you still use an email login code.</p>
      </div>
      <div class="pt-2">
        <button type="submit" class="srms-btn-green w-full text-lg py-3 shadow-md shadow-green-500/20">Sign in</button>
      </div>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-100 relative z-20">
      <p class="font-bold text-gray-800 uppercase tracking-wider text-[11px] text-center mb-3">Don't have an account?</p>

      <a href="<?= url('/account/register/choose') ?>" class="w-full flex items-center justify-center px-4 py-3 rounded-xl border-2 border-green-600 text-green-700 font-bold text-sm bg-white hover:bg-green-50 transition" style="text-decoration:none;">Sign up</a>

      <div class="flex flex-col gap-1 mt-4 text-center">
        <a href="<?= url('/account/login') ?>" class="text-xs font-bold text-gray-500 hover:text-red-600 transition">Choose a different role login</a>
      </div>
    </div>
  </div>
</div>





<?php require __DIR__ . '/layout-footer.php'; ?>
