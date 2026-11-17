<?php
/** Requires $error, $notice, $email, $loginPath in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="auth-shell">
  <div class="auth-card">
    <?php require __DIR__ . '/partials/auth-mark.php'; ?>
    <div class="auth-head">
      <h1>Enter the email code</h1>
      <p>We sent a 6-digit code to <strong><?= e($email ?? 'your email') ?></strong>. It expires in 10 minutes.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="flash-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($notice)): ?>
      <div class="flash-success"><?= e($notice) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/account/forgot-password/verify') ?>">
      <?= csrfField() ?>
      <div class="auth-field">
        <label for="reset-code">6-digit code</label>
        <input id="reset-code" type="text" name="code" required autofocus maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" style="text-align:center;font-size:1.4rem;letter-spacing:0.5em;font-family:monospace" />
      </div>
      <button type="submit" class="auth-btn-primary">Verify code</button>
    </form>

    <form method="post" action="<?= url('/account/forgot-password/verify') ?>" style="margin-top:0.6rem">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="resend" />
      <button type="submit" class="auth-btn-secondary">Resend code</button>
    </form>

    <div class="auth-link-row">
      <a href="<?= url($loginPath ?? '/account/login') ?>">&larr; Back to sign in</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
