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
    
    <div class="mt-8 pt-6 border-t border-gray-100 relative z-20">
      <p class="font-bold text-gray-800 uppercase tracking-wider text-[11px] text-center mb-3">Don't have an account?</p>

      <!-- Sign up accordion dropdown -->
      <div class="relative" id="loginSignupDropWrapper">
        <button type="button" id="loginSignupDropBtn"
          class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 border-green-600 text-green-700 font-bold text-sm bg-white hover:bg-green-50 transition">
          <span>Sign up — choose your role</span>
          <span id="loginSignupCaret" style="font-size:0.75rem;transition:transform 0.2s;">▼</span>
        </button>
        <div id="loginSignupDropMenu" class="hidden mt-2 rounded-xl border border-gray-100 bg-white shadow-lg overflow-hidden">
          <div style="padding:0.35rem 1rem 0.1rem;font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;background:#f8fafc;border-bottom:1px solid #f1f5f9;">Course Portals</div>
          <a href="<?= url('/account/login/organisation-admin') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-red-50 hover:text-red-700 border-b border-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#fef2f2;color:#dc2626;">🏢</span> Organisation (Course Provider)
          </a>
          <a href="<?= url('/account/login/course-branch-admin') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-red-50 hover:text-red-700 border-b border-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#fff7ed;color:#ea580c;">🏬</span> Branch Admin (Course Org)
          </a>
          <a href="<?= url('/account/register') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-green-700 border-b border-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#f0fdf4;color:#16a34a;">🎓</span> Student
          </a>
          <a href="<?= url('/account/register/trainer') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-green-700 border-b border-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#f0fdf4;color:#15803d;">👨&#x200d;🏫</span> Tutor / Teacher
          </a>
          <div style="padding:0.35rem 1rem 0.1rem;font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;background:#f8fafc;border-bottom:1px solid #f1f5f9;">Attachment Portals</div>
          <a href="<?= url('/account/register/attachment-trainer') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-green-700 border-b border-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#faf5ff;color:#7c3aed;">🤝</span> Organisation (Attachment Provider)
          </a>
          <a href="<?= url('/account/login/branch-admin') ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50" style="text-decoration:none;">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background:#f1f5f9;color:#334155;">📍</span> Branch (Attachment Org)
          </a>
        </div>
      </div>

      <div class="flex flex-col gap-1 mt-4 text-center">
        <a href="<?= url('/account/login') ?>" class="text-xs font-bold text-gray-500 hover:text-red-600 transition">Choose a different role login</a>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var btn = document.getElementById('loginSignupDropBtn');
  var menu = document.getElementById('loginSignupDropMenu');
  var caret = document.getElementById('loginSignupCaret');
  if (btn && menu) {
    btn.addEventListener('click', function() {
      var open = !menu.classList.contains('hidden');
      menu.classList.toggle('hidden');
      if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
    });
  }
})();
</script>


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
