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
<body class="antialiased" style="background:var(--ke-paper);color:var(--ke-ink)">
  <div class="flag-stripe" aria-hidden="true"></div>
  <header class="sticky top-0 z-40 bg-white" style="border-bottom:1px solid var(--ke-line)">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg text-white" style="background:var(--ke-red)">
          <?= storeLogoHtml('h-full w-full rounded-lg object-contain', 'h-4 w-4 text-white') ?>
        </span>
        <span>
          <span class="block text-sm font-black uppercase tracking-[0.18em]" style="color:var(--ke-black)"><?= e(appName()) ?></span>
          <span class="block text-[10px] font-bold uppercase tracking-[0.28em]" style="color:var(--ke-green)">LMS</span>
        </span>
      </a>
      <nav class="flex items-center gap-3 text-xs font-black uppercase tracking-widest">
        <a href="<?= url('/account/register') ?>" class="btn-secondary">Student register</a>
        <a href="<?= url('/account/register/trainer') ?>" class="btn-secondary">Trainer register</a>
        <a href="<?= url('/account/login') ?>" class="btn-primary">Sign in</a>
        <a href="<?= url('/admin/login') ?>" class="btn-danger">Super Admin</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="text-white" style="background:var(--ke-black)">
      <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:py-24">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.28em]" style="color:#b8e0cc">Learning management system</p>
          <h1 class="mt-4 text-4xl font-black leading-tight sm:text-5xl">Training, people, and profiles — organised by role.</h1>
          <p class="mt-5 max-w-xl text-sm font-medium leading-6" style="color:#e8f5ee">Super Admin builds roles and forms. Organisation admins and attachment trainers get admin-like tools. Trainers and students work under their organisation and complete assigned profile forms.</p>
          <div class="mt-8 flex flex-wrap gap-3">
            <a href="<?= url('/account/register') ?>" class="btn-primary">Student register</a>
            <a href="<?= url('/account/register/trainer') ?>" class="btn-secondary">Trainer register</a>
            <a href="<?= url('/account/login') ?>" class="btn-secondary">Sign in</a>
            <?php if (!empty($needsSetup)): ?>
              <a href="<?= url('/setup') ?>" class="btn-danger">Run first-time setup</a>
            <?php else: ?>
              <a href="<?= url('/admin/login') ?>" class="btn-danger">Super Admin</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="rounded-2xl p-6" style="border:1px solid rgba(184,224,204,.35);background:rgba(0,107,63,.18)">
          <p class="text-[11px] font-black uppercase tracking-widest" style="color:#b8e0cc">Who signs in where</p>
          <ul class="mt-4 space-y-3 text-sm font-semibold">
            <li class="rounded-lg p-4" style="border:1px solid rgba(187,0,0,.45);background:rgba(187,0,0,.18)"><span class="block text-xs uppercase tracking-widest" style="color:#ffd0d0">Platform owner</span>Super Admin login — separate dashboard</li>
            <li class="rounded-lg p-4" style="border:1px solid rgba(184,224,204,.35);background:rgba(0,107,63,.22)"><span class="block text-xs uppercase tracking-widest" style="color:#b8e0cc">Students</span>Student login, then the student dashboard</li>
            <li class="rounded-lg p-4" style="border:1px solid rgba(184,224,204,.35);background:rgba(0,107,63,.22)"><span class="block text-xs uppercase tracking-widest" style="color:#b8e0cc">Trainers / tutors / teachers</span>Trainer login, then the trainer dashboard</li>
            <li class="rounded-lg p-4" style="border:1px solid rgba(184,224,204,.35);background:rgba(0,107,63,.22)"><span class="block text-xs uppercase tracking-widest" style="color:#b8e0cc">Attachment trainers</span>Attachment trainer login, then that dashboard</li>
            <li class="rounded-lg p-4" style="border:1px solid rgba(184,224,204,.35);background:rgba(0,107,63,.22)"><span class="block text-xs uppercase tracking-widest" style="color:#b8e0cc">Organisation admins</span>Organisation admin login, then the organisation dashboard</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Roles</p>
      <h2 class="mt-3 text-3xl font-black" style="color:var(--ke-black)">Separate power, same platform</h2>
      <div class="mt-8 grid gap-4 md:grid-cols-2">
        <?php
        $fallbackRoles = [
            ['slug' => 'organisation_admin', 'name' => 'Organisation Admin', 'description' => 'Admin-like tools inside an organisation.', 'has_admin_features' => true],
            ['slug' => 'attachment_trainer', 'name' => 'Attachment Trainer', 'description' => 'Admin-like tools for students in the organisation.', 'has_admin_features' => true],
            ['slug' => 'trainer', 'name' => 'Trainer / Tutor / Teacher', 'description' => 'Under organisation power. Dashboard and profile only.', 'has_admin_features' => false],
            ['slug' => 'student', 'name' => 'Student', 'description' => 'Under organisation power. Completes assigned profile forms.', 'has_admin_features' => false],
        ];
        $cards = $roles ?: $fallbackRoles;
        foreach ($cards as $role):
          $adminLike = !empty($role['has_admin_features']);
          $loginHref = !empty($role['slug']) ? url(\App\Core\LoginRoles::loginPath((string) $role['slug'])) : url('/account/login');
        ?>
          <article class="rounded-xl bg-white p-6" style="border:2px solid <?= $adminLike ? 'var(--ke-red)' : 'var(--ke-green)' ?>">
            <p class="text-[11px] font-black uppercase tracking-widest" style="color: <?= $adminLike ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= $adminLike ? 'Admin-like' : 'Under organisation' ?></p>
            <h3 class="mt-2 text-xl font-black"><?= e($role['name']) ?></h3>
            <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)"><?= e($role['description'] ?? '') ?></p>
            <a href="<?= $loginHref ?>" class="btn-primary mt-4 inline-flex" style="padding:0.4rem 0.85rem;">Sign in as <?= e($role['name']) ?></a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <footer class="py-8 text-center text-xs font-semibold" style="border-top:1px solid var(--ke-line);color:var(--ke-muted)">
    &copy; <?= date('Y') ?> <?= e(appName()) ?>. LMS.
  </footer>
</body>
</html>
