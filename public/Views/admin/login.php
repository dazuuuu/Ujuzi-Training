<?php
/** Requires $error in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Pentagon Collections</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-[#0a0a0a] text-white antialiased min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-12 h-12 bg-black text-white rounded-xl border border-neutral-300 mb-3">
        <?= storeLogoHtml('w-full h-full object-contain rounded-xl', 'w-6 h-6 text-white') ?>
      </div>
      <h1 class="font-serif-heading text-2xl font-bold tracking-widest uppercase">Pentagon Admin</h1>
      <p class="text-xs text-neutral-500 mt-1">Sign in to manage products, categories &amp; orders</p>
    </div>

    <form method="post" action="<?= url('/admin/login') ?>" class="bg-black border border-neutral-700 rounded-xl p-6 space-y-4 shadow-2xl">
      <?= csrfField() ?>
      <?php if ($error): ?>
        <div class="bg-rose-950 border border-rose-600/40 text-rose-300 text-xs font-semibold rounded-lg p-3"><?= e($error) ?></div>
      <?php endif; ?>
      <div>
        <label class="text-[11px] font-bold text-neutral-300 uppercase tracking-wider">Email</label>
        <input type="email" name="email" required autofocus class="w-full mt-1 bg-[#0a0a0a] border border-neutral-700 text-white text-sm rounded-lg px-3 py-2.5 focus:outline-none focus:border-white" />
      </div>
      <div>
        <label class="text-[11px] font-bold text-neutral-300 uppercase tracking-wider">Password</label>
        <input type="password" name="password" required class="w-full mt-1 bg-[#0a0a0a] border border-neutral-700 text-white text-sm rounded-lg px-3 py-2.5 focus:outline-none focus:border-white" />
      </div>
      <button type="submit" class="w-full bg-white hover:bg-neutral-100 text-black text-xs font-bold py-3 rounded-lg uppercase tracking-widest transition-colors cursor-pointer">Sign In</button>
    </form>

    <p class="text-center text-[11px] text-neutral-500 mt-6">
      <a href="<?= url('/') ?>" class="hover:text-white">&larr; Back to storefront</a>
    </p>
  </div>
</body>
</html>
