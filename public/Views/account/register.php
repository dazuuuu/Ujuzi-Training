<?php
/** Student or organisation-admin registration. Requires $heading, $blurb, $action, $error, $email. */
require __DIR__ . '/layout-header.php';
?>

<div class="auth-shell">
  <div class="auth-card">
    <?php require __DIR__ . '/partials/auth-mark.php'; ?>
    <div class="auth-head">
      <h1><?= e($heading) ?></h1>
      <p>Already registered? <a href="<?= url($loginUrl ?? '/account/login') ?>">Sign in</a></p>
    </div>

    <?php if ($error): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>">
      <?= csrfField() ?>
      <div class="auth-field">
        <label for="reg-email">Email</label>
        <input id="reg-email" type="email" name="email" required value="<?= e($email ?? '') ?>" autocomplete="email" />
      </div>
      <div class="auth-field">
        <label for="reg-password">Password</label>
        <input id="reg-password" type="password" name="password" required minlength="8" autocomplete="new-password" />
      </div>
      <div class="auth-field">
        <label for="reg-password-confirm">Confirm password</label>
        <input id="reg-password-confirm" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" />
      </div>
      <button type="submit" class="auth-btn-primary">Create account</button>
    </form>

    <p class="auth-fine-print"><?= e($blurb) ?></p>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
