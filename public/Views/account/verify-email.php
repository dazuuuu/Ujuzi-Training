<?php
/** Result of the email's Verify button. Requires $ok and $email. Closes itself when it can. */
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $ok ? 'Email verified' : 'Link not valid' ?> | <?= e(appName()) ?></title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f3f7f2;font-family:Inter,system-ui,sans-serif;color:#111827;padding:16px;box-sizing:border-box}
  .box{max-width:420px;width:100%;background:#fff;border-radius:18px;padding:32px 24px;text-align:center;box-shadow:0 12px 32px rgba(0,0,0,.08)}
  .icon{width:64px;height:64px;margin:0 auto 14px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff}
  h1{font-size:1.35rem;margin:0 0 8px} p{color:#4b5563;line-height:1.55;margin:0 0 16px}
  a{display:inline-block;padding:.7rem 1.2rem;border-radius:12px;background:#006b3f;color:#fff;font-weight:800;text-decoration:none}
</style></head>
<body>
  <div class="box">
    <?php if ($ok): ?>
      <div class="icon" style="background:#006b3f">✓</div>
      <h1>Email verified</h1>
      <p><strong><?= e($email) ?></strong> is confirmed and your account is unlocked. This page will close by itself.</p>
      <a href="<?= url('/account/login') ?>">Go to sign in</a>
      <script>setTimeout(function () { window.close(); }, 2500);</script>
    <?php else: ?>
      <div class="icon" style="background:#bb0000">!</div>
      <h1>This link doesn't work</h1>
      <p>It may have expired (links last 2 days), been used already, or the email was taken by another account in the meantime. Sign in and send a new link.</p>
      <a href="<?= url('/account/login') ?>">Sign in</a>
    <?php endif; ?>
  </div>
</body></html>
