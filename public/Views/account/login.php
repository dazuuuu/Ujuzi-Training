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

    <div class="flex mb-6 bg-gray-100 rounded-lg p-1 text-xs font-bold uppercase tracking-wider relative z-20">
      <button type="button" data-tab="email" class="account-tab flex-1 py-2.5 rounded-md transition-colors text-gray-600 focus:outline-none">Email</button>
      <button type="button" data-tab="phone" class="account-tab flex-1 py-2.5 rounded-md transition-colors text-gray-600 focus:outline-none">Phone Number</button>
    </div>

    <?php if ($error): ?>
      <div class="bg-red-50 text-red-700 p-4 rounded-lg text-sm font-semibold mb-6 flex items-start gap-3 border border-red-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url($loginPath) ?>" id="email-form" class="space-y-5 relative z-20">
      <?= csrfField() ?>
      <input type="hidden" name="method" value="email" />
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Email Address</label>
        <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com" autocomplete="email" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-gray-900" />
      </div>
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-sm font-bold text-gray-700">Password</label>
          <a href="<?= url('/account/forgot-password?role=' . rawurlencode((string) ($roleSlug ?? ''))) ?>" class="text-xs font-semibold text-red-600 hover:text-blue-800 transition">Forgot password?</a>
        </div>
        <input type="password" name="password" autocomplete="current-password" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-gray-900" />
        <p class="mt-2 text-xs text-gray-500">Leave blank only if an admin created your account and you still use an email login code.</p>
      </div>
      <div class="pt-2">
        <button type="submit" class="srms-btn-green w-full text-lg py-3 shadow-md shadow-green-500/20">Sign in</button>
      </div>
    </form>

    <form method="post" action="<?= url($loginPath) ?>" id="phone-form" class="space-y-5 hidden relative z-20">
      <?= csrfField() ?>
      <input type="hidden" name="method" value="phone" />
      <div>
        <label class="block text-sm font-bold text-gray-700 mb-1">Phone Number</label>
        <input type="tel" name="phone" required value="<?= e($old['phone'] ?? '') ?>" placeholder="254712345678" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-gray-900 font-mono" />
      </div>
      <div class="pt-2">
        <button type="submit" class="srms-btn-green w-full text-lg py-3 shadow-md shadow-green-500/20">Continue</button>
      </div>
      <p class="text-xs text-gray-500 text-center mt-3">Use the exact phone number your admin saved.</p>
    </form>
    
    <div class="mt-8 pt-6 border-t border-gray-100 text-center space-y-3 relative z-20">
      <p class="font-bold text-gray-800 uppercase tracking-wider text-[11px]">Don't have an account?</p>
      
      <div class="flex flex-col gap-2 mt-2">
        <?php if ($registerPath): ?>
          <a href="<?= url($registerPath) ?>" class="srms-btn-green-outline text-sm w-full py-2">Register for this role</a>
        <?php else: ?>
          <a href="<?= url('/account/register') ?>" class="srms-btn-green-outline text-sm w-full py-2">Register as Student</a>
          <a href="<?= url('/account/register/trainer') ?>" class="srms-btn-green-outline text-sm w-full py-2">Register as Tutor</a>
          <a href="<?= url('/account/register/attachment-trainer') ?>" class="srms-btn-green-outline text-sm w-full py-2">Register as Organisation providing Attachment</a>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-1 mt-4">
        <a href="<?= url('/account/login') ?>" class="text-xs font-bold text-gray-500 hover:text-red-600 transition">Choose a different role login</a>
        <a href="<?= url('/admin/login') ?>" class="text-xs font-bold text-gray-500 hover:text-red-500 transition">Super Admin login</a>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var tabs = document.querySelectorAll('.account-tab');
  var forms = { email: document.getElementById('email-form'), phone: document.getElementById('phone-form') };
  var initial = <?= json_encode($method === 'phone' ? 'phone' : 'email') ?>;

  function setActive(method) {
    tabs.forEach(function (t) {
      var active = t.getAttribute('data-tab') === method;
      t.classList.toggle('bg-white', active);
      t.classList.toggle('shadow-sm', active);
      t.style.color = active ? '#006b3f' : '#2f3f37';
      t.style.fontWeight = active ? '800' : '700';
    });
    forms.email.classList.toggle('hidden', method !== 'email');
    forms.phone.classList.toggle('hidden', method !== 'phone');
  }

  tabs.forEach(function (t) {
    t.addEventListener('click', function () { setActive(t.getAttribute('data-tab')); });
  });
  setActive(initial);
})();
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
