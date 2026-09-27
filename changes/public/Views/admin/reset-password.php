<?php
/** Requires $error in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New Super Admin password | <?= e(appName()) ?></title>
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
      <h1 class="font-serif-heading text-2xl font-bold">Choose a new password</h1>
      <p class="text-xs mt-1" style="color:#b8e0cc">At least 8 characters</p>
    </div>
    <form method="post" action="<?= url('/admin/forgot-password/new') ?>" class="rounded-xl p-6 space-y-4" style="background:#1a1a1a;border:1px solid var(--ke-green)">
      <?= csrfField() ?>
      <?php if (!empty($error)): ?>
        <div class="flash-error"><?= e($error) ?></div>
      <?php endif; ?>
      <div>
        <label class="text-[11px] font-bold uppercase tracking-wider" style="color:#b8e0cc">New password</label>
        <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full mt-1 text-sm rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase tracking-wider" style="color:#b8e0cc">Confirm password</label>
        <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full mt-1 text-sm rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
      </div>
      <button type="submit" class="btn-primary btn-block">Save password</button>
    </form>
  </div>
  </div>
</body>
</html>
