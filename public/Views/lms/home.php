<?php
/** Public LMS landing. Requires $roles, $needsSetup. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(appName()) ?> | Learning Management System</title>
  <meta name="description" content="Role-based learning platform for organisation admins, trainers, attachment trainers, and students." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-white text-[#0a0a0a] antialiased">
  <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-black bg-black text-white">
          <?= storeLogoHtml('h-full w-full rounded-lg object-contain', 'h-4 w-4 text-white') ?>
        </span>
        <span>
          <span class="block text-sm font-black uppercase tracking-[0.18em]"><?= e(appName()) ?></span>
          <span class="block text-[10px] font-bold uppercase tracking-[0.28em] text-neutral-500">LMS</span>
        </span>
      </a>
      <nav class="flex items-center gap-3 text-xs font-black uppercase tracking-widest">
        <a href="<?= url('/account/login') ?>" class="text-neutral-700 hover:text-black">Sign in</a>
        <a href="<?= url('/admin/login') ?>" class="rounded-lg bg-black px-4 py-2 text-white hover:bg-neutral-900">Super Admin</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="border-b border-neutral-200 bg-black text-white">
      <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:py-24">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.28em] text-neutral-400">Learning management system</p>
          <h1 class="mt-4 text-4xl font-black leading-tight sm:text-5xl">Training, people, and profiles — organised by role.</h1>
          <p class="mt-5 max-w-xl text-sm font-medium leading-6 text-neutral-300">Super Admin builds roles and forms. Organisation admins and attachment trainers get admin-like tools. Trainers and students work under their organisation and complete assigned profile forms.</p>
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="<?= url('/account/login') ?>" class="rounded-lg bg-white px-5 py-3 text-xs font-black uppercase tracking-widest text-black hover:bg-neutral-100">User login</a>
            <?php if (!empty($needsSetup)): ?>
              <a href="<?= url('/setup') ?>" class="rounded-lg border border-white px-5 py-3 text-xs font-black uppercase tracking-widest text-white">Run first-time setup</a>
            <?php else: ?>
              <a href="<?= url('/admin/login') ?>" class="rounded-lg border border-white px-5 py-3 text-xs font-black uppercase tracking-widest text-white">Super Admin</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="rounded-2xl border border-white/15 bg-white/5 p-6">
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-400">Who signs in where</p>
          <ul class="mt-4 space-y-3 text-sm font-semibold">
            <li class="rounded-lg border border-white/10 p-4"><span class="block text-xs uppercase tracking-widest text-neutral-400">Platform owner</span>Super Admin login</li>
            <li class="rounded-lg border border-white/10 p-4"><span class="block text-xs uppercase tracking-widest text-neutral-400">Everyone else</span>User login — dashboard follows their role</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Roles</p>
      <h2 class="mt-3 text-3xl font-black">Separate power, same platform</h2>
      <div class="mt-8 grid gap-4 md:grid-cols-2">
        <?php
        $fallbackRoles = [
            ['name' => 'Organisation Admin', 'description' => 'Admin-like tools inside an organisation.', 'has_admin_features' => true],
            ['name' => 'Attachment Trainer', 'description' => 'Admin-like tools for students in the organisation.', 'has_admin_features' => true],
            ['name' => 'Trainer / Tutor / Teacher', 'description' => 'Under organisation power. Dashboard and profile only.', 'has_admin_features' => false],
            ['name' => 'Student', 'description' => 'Under organisation power. Completes assigned profile forms.', 'has_admin_features' => false],
        ];
        $cards = $roles ?: $fallbackRoles;
        foreach ($cards as $role):
        ?>
          <article class="rounded-xl border border-neutral-300 bg-white p-6">
            <p class="text-[11px] font-black uppercase tracking-widest text-neutral-500"><?= !empty($role['has_admin_features']) ? 'Admin-like' : 'Under organisation' ?></p>
            <h3 class="mt-2 text-xl font-black"><?= e($role['name']) ?></h3>
            <p class="mt-2 text-sm font-medium text-neutral-700"><?= e($role['description'] ?? '') ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="border-t border-neutral-200 py-8 text-center text-xs font-semibold text-neutral-500">
    &copy; <?= date('Y') ?> <?= e(appName()) ?>. LMS.
  </footer>
</body>
</html>
