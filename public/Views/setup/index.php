<?php
/** Requires $errors, $old, $ranMigrations in scope. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Setup | Pentagon Collections</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-white text-black antialiased">
  <main class="mx-auto grid min-h-screen max-w-6xl items-center gap-8 px-4 py-8 lg:grid-cols-[0.9fr_1.1fr]">
    <section class="rounded-2xl bg-black p-8 text-white">
      <div class="mb-10 flex h-14 w-14 items-center justify-center rounded-xl border border-white/20 bg-white text-black">
        <?= pentagonLogoSvg('w-8 h-8 text-black') ?>
      </div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-300">First Run Setup</p>
      <h1 class="mt-3 font-serif-heading text-4xl font-black leading-tight">Prepare your shop dashboard.</h1>
      <div class="mt-8 space-y-3 text-sm font-semibold text-neutral-200">
        <div class="rounded-lg border border-white/15 p-4">
          <p class="font-black text-white">1. Database migrations</p>
          <p class="mt-1 text-neutral-300"><?= (int) $ranMigrations ?> pending migration(s) ran on this request.</p>
        </div>
        <div class="rounded-lg border border-white/15 p-4">
          <p class="font-black text-white">2. Admin account</p>
          <p class="mt-1 text-neutral-300">Create the first admin who will manage products, offers, orders, and settings.</p>
        </div>
        <div class="rounded-lg border border-white/15 p-4">
          <p class="font-black text-white">3. Store logo</p>
          <p class="mt-1 text-neutral-300">Optional now. You can always update it later in Admin Settings.</p>
        </div>
      </div>
    </section>

    <section class="rounded-2xl border border-neutral-300 bg-white p-6 shadow-sm sm:p-8">
      <div class="mb-6">
        <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Create Admin</p>
        <h2 class="mt-2 text-2xl font-black text-black">Finish Website Setup</h2>
        <p class="mt-1 text-sm font-medium text-neutral-700">After this, you will be signed in and taken to the admin dashboard.</p>
      </div>

      <?php foreach ($errors as $error): ?>
        <div class="mb-3 rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-bold text-rose-800"><?= e($error) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= url('/setup') ?>" enctype="multipart/form-data" class="space-y-5">
        <?= csrfField() ?>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Store Owner Name <span class="font-semibold normal-case text-neutral-500">(optional)</span></label>
          <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none" />
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Admin Email</label>
          <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Password</label>
            <input type="password" name="password" required minlength="8" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none" />
          </div>
          <div>
            <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Confirm Password</label>
            <input type="password" name="password_confirm" required minlength="8" class="mt-2 w-full rounded-lg border border-neutral-400 bg-white px-4 py-3 text-sm font-semibold text-black focus:border-black focus:outline-none" />
          </div>
        </div>
        <div>
          <label class="text-[11px] font-black uppercase tracking-widest text-neutral-700">Upload Logo <span class="font-semibold normal-case text-neutral-500">(optional)</span></label>
          <input type="file" name="store_logo" accept="image/*" class="mt-2 block w-full rounded-lg border border-neutral-400 bg-white p-3 text-sm font-semibold text-black" />
          <p class="mt-2 text-xs font-medium text-neutral-600">This logo appears in the storefront navbar, admin login, and order tracking page.</p>
        </div>
        <button type="submit" class="w-full rounded-lg bg-black px-6 py-4 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900">Run Setup &amp; Enter Admin</button>
      </form>
    </section>
  </main>
</body>
</html>
