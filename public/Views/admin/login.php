<?php
/** Requires $error in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Login | <?= e(appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="antialiased min-h-screen flex flex-col" style="background:var(--ke-black);color:var(--ke-white)">
  <div class="flag-stripe" aria-hidden="true"></div>
  <div class="flex-1 flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl mb-3" style="background:var(--ke-red)">
        <?= storeLogoHtml('w-full h-full object-contain rounded-xl', 'w-6 h-6 text-white') ?>
      </div>
      <h1 class="font-serif-heading text-2xl font-bold tracking-widest uppercase"><?= e(appName()) ?></h1>
      <p class="text-xs mt-1" style="color:#b8e0cc">Super Admin — roles, forms, organisations, and users</p>
    </div>

    <form method="post" action="<?= url('/admin/login') ?>" class="rounded-xl p-6 space-y-4" style="background:#1a1a1a;border:1px solid var(--ke-green)">
      <?= csrfField() ?>
      <?php if ($error): ?>
        <div class="flash-error"><?= e($error) ?></div>
      <?php endif; ?>
      <div>
        <label class="text-[11px] font-bold uppercase tracking-wider" style="color:#b8e0cc">Email</label>
        <input type="email" name="email" required autofocus class="w-full mt-1 text-sm rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase tracking-wider" style="color:#b8e0cc">Password</label>
        <input type="password" name="password" required class="w-full mt-1 text-sm rounded-lg px-3 py-2.5" style="background:#111;border:1px solid #6b7f74;color:#fff" />
      </div>
      <button type="submit" class="btn-primary btn-block">Sign In</button>
    </form>

    <p class="text-center text-[11px] mt-6" style="color:#b8e0cc">
      <a href="<?= url('/account/login') ?>" class="btn-secondary" style="padding:0.4rem 0.8rem;">User login</a>
      <a href="<?= url('/') ?>" class="ml-2 underline" style="color:#b8e0cc">&larr; Back to LMS</a>
    </p>
  </div>
  </div>
</body>
</html>
