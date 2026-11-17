<?php
/** Role-specific LMS login. Requires $error, $method, $old, $roleSlug, $roleMeta, $loginPath. */
use App\Core\LoginRoles;
require __DIR__ . '/layout-header.php';
$error = $error ?? '';
$old = $old ?? [];
$meta = $roleMeta ?? LoginRoles::meta($roleSlug ?? '');
$loginPath = $loginPath ?? '/account/login';
$registerPath = $registerPath ?? LoginRoles::registerPath((string) ($roleSlug ?? ''));
?>

<div class="auth-shell">
  <div class="auth-card">
    <?php require __DIR__ . '/partials/auth-mark.php'; ?>
    <div class="auth-head">
      <h1><?= e($meta['heading'] ?? 'Welcome back') ?></h1>
      <?php if ($registerPath): ?>
        <p>First time here? <a href="<?= url($registerPath) ?>">Sign up for free</a></p>
      <?php else: ?>
        <p><?= e($meta['blurb'] ?? 'Use the email and password for this role.') ?></p>
      <?php endif; ?>
    </div>

    <?php if ($error): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url($loginPath) ?>">
      <?= csrfField() ?>
      <div class="auth-field">
        <label for="login-id">Email or phone number</label>
        <input id="login-id" type="text" name="login" required value="<?= e($old['login'] ?? '') ?>" placeholder="you@example.com or 0712 345 678" autocomplete="username" data-phone-smart />
      </div>
      <div class="auth-field">
        <div class="auth-row">
          <label for="login-password">Password</label>
          <a href="<?= url('/account/forgot-password?role=' . rawurlencode((string) ($roleSlug ?? ''))) ?>">Forgot password?</a>
        </div>
        <input id="login-password" type="password" name="password" autocomplete="current-password" />
      </div>
      <button type="submit" class="auth-btn-primary">Sign in</button>
    </form>

    <?php if ($registerPath): ?>
      <div class="auth-link-row">
        <a href="<?= url($registerPath) ?>">Sign up</a>
      </div>
    <?php endif; ?>

    <div class="auth-divider">or</div>
    <a href="<?= url('/account/login') ?>" class="auth-btn-secondary">Choose a different role login</a>

    <p class="auth-fine-print">
      Leave the password blank only if an admin created your account and you still use an email login code.
    </p>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
