<?php
/** Requires $error, $notice, $email in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Enter reset code | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="antialiased min-h-screen flex flex-col" style="background:var(--ke-black);color:var(--ke-white)">
  <div class="flag-stripe" aria-hidden="true"></div>
  <div class="flex-1 flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="font-serif-heading text-2xl font-bold">Enter the email code</h1>
      <p class="text-xs mt-2" style="color:#b8e0cc">Sent to <?= e($email ?? 'your email') ?>. Expires in 10 minutes.</p>
    </div>
    <div class="rounded-xl p-6 space-y-4" style="background:#1a1a1a;border:1px solid var(--ke-green)">
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="flash-error"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($notice)): ?>
        <p class="text-sm font-semibold" style="color:#b8e0cc"><?= e($notice) ?></p>
      <?php endif; ?>
      <form method="post" action="<?= url('/admin/forgot-password/verify') ?>" class="space-y-4">
        <?= csrfField() ?>
        <input type="text" name="code" required autofocus maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" class="w-full text-center text-2xl font-mono tracking-[0.4em] rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
        <button type="submit" class="btn-primary btn-block">Verify code</button>
      </form>
      <form method="post" action="<?= url('/admin/forgot-password/verify') ?>">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="resend" />
        <button type="submit" class="btn-secondary btn-block">Resend code</button>
      </form>
    </div>
    <p class="text-center text-[11px] mt-6"><a href="<?= url('/admin/login') ?>" class="underline" style="color:#b8e0cc">&larr; Back to login</a></p>
  </div>
  </div>
</body>
</html>
