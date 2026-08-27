<?php
/** Requires $error, $email in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Super Admin password | <?= e(appName()) ?></title>
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
      <h1 class="font-serif-heading text-2xl font-bold tracking-widest uppercase"><?= e(appName()) ?></h1>
      <p class="text-xs mt-1" style="color:#b8e0cc">We email a 6-digit OTP using Super Admin → Settings SMTP</p>
    </div>
    <form method="post" action="<?= url('/admin/forgot-password') ?>" class="rounded-xl p-6 space-y-4" style="background:#1a1a1a;border:1px solid var(--ke-green)">
      <?= csrfField() ?>
      <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success"><?= e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="flash-error"><?= e($error) ?></div>
      <?php endif; ?>
      <div>
        <label class="text-[11px] font-bold uppercase tracking-wider" style="color:#b8e0cc">Email</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>" autofocus class="w-full mt-1 text-sm rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
      </div>
      <button type="submit" class="btn-primary btn-block">Send code</button>
    </form>
    <p class="text-center text-[11px] mt-6" style="color:#b8e0cc">
      <a href="<?= url('/admin/login') ?>" class="underline" style="color:#b8e0cc">&larr; Back to Super Admin login</a>
    </p>
  </div>
  </div>
</body>
</html>
