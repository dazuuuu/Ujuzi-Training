<?php
/** Requires $error, $loginPath in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="auth-shell">
  <div class="auth-card">
    <?php require __DIR__ . '/partials/auth-mark.php'; ?>
    <div class="auth-head">
      <h1>Choose a new password</h1>
      <p>Use at least 8 characters. You will sign in with this password next time.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/account/forgot-password/new') ?>">
      <?= csrfField() ?>
      <div class="auth-field">
        <label for="new-password">New password</label>
        <input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" />
      </div>
      <div class="auth-field">
        <label for="new-password-confirm">Confirm password</label>
        <input id="new-password-confirm" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" />
      </div>
      <button type="submit" class="auth-btn-primary">Save password</button>
    </form>

    <div class="auth-link-row">
      <a href="<?= url($loginPath ?? '/account/login') ?>">&larr; Back to sign in</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
