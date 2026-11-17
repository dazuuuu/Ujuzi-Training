<?php
/** Requires $error, $email, $role, $loginPath in scope. */
require __DIR__ . '/layout-header.php';
$loginPath = $loginPath ?? '/account/login';
?>

<div class="auth-shell">
  <div class="auth-card">
    <?php require __DIR__ . '/partials/auth-mark.php'; ?>
    <div class="auth-head">
      <h1>Forgot your password?</h1>
      <p>We'll email you a 6-digit code to reset it.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/account/forgot-password') ?>">
      <?= csrfField() ?>
      <input type="hidden" name="role" value="<?= e($role ?? '') ?>" />
      <div class="auth-field">
        <label for="forgot-email">Email address</label>
        <input id="forgot-email" type="email" name="email" required value="<?= e($email ?? '') ?>" autocomplete="email" />
      </div>
      <button type="submit" class="auth-btn-primary">Send me the code</button>
    </form>

    <div class="auth-link-row">
      <a href="<?= url($loginPath) ?>">&larr; Back to sign in</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
