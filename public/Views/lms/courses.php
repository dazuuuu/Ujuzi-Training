<?php
/** Public course catalogue. Requires $courses. */
$courses = $courses ?? [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title>Courses | <?= e(appName()) ?></title>
  <meta name="description" content="Browse published courses open to every student on <?= e(appName()) ?>." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
</head>
<body class="antialiased min-h-screen flex flex-col font-sans" style="background-color: #f8fafc; color: #1e293b;">
  <!-- Header -->
  <header class="w-full py-4 bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 group">
        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-900 text-white shadow-sm shrink-0">
          🎓
        </div>
        <div class="flex flex-col text-left leading-none">
          <span class="font-serif-heading text-xl font-bold tracking-tight text-blue-900"><?= e(appName()) ?>.</span>
        </div>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-700">
        <a href="<?= url('/') ?>" class="hover:text-blue-600 transition pb-1">Home</a>
        <a href="<?= url('/courses') ?>" class="text-blue-600 border-b-2 border-blue-600 pb-1">Courses</a>
        <a href="<?= url('/account/login') ?>" class="hover:text-blue-600 transition pb-1">Sign in</a>
        <a href="<?= url('/account/register/choose') ?>" class="hover:text-blue-600 transition pb-1">Sign up</a>
      </nav>
      <div class="flex items-center gap-4">
        <a href="<?= url('/account/register/choose') ?>" class="srms-btn-orange hidden sm:inline-flex">Enroll Now</a>
        <button class="md:hidden text-gray-700 text-2xl">☰</button>
      </div>
    </div>
  </header>

  <!-- Page header -->
  <section class="bg-blue-900 text-white py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
      <div class="inline-block bg-white/10 text-white px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase mb-4">
        <?= count($courses) ?> course<?= count($courses) === 1 ? '' : 's' ?> available
      </div>
      <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight">All Courses</h1>
      <p class="text-blue-200 text-lg max-w-xl mx-auto mt-4">Browse courses open to every student. Sign up to enroll, track progress, and earn a certificate.</p>
    </div>
  </section>

  <!-- Course grid -->
  <section class="py-16 flex-1">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      <?php if (!$courses): ?>
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
          <p class="text-gray-500 font-semibold">No public courses are published yet. Check back soon.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php foreach ($courses as $course): ?>
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition overflow-hidden flex flex-col">
              <?php if (!empty($course['cover_image'])): ?>
                <img src="<?= e(imageUrl($course['cover_image'])) ?>" alt="<?= e($course['title']) ?>" class="w-full h-44 object-cover">
              <?php else: ?>
                <div class="w-full h-44 bg-gradient-to-br from-blue-700 to-blue-400 flex items-center justify-center">
                  <span class="text-white text-5xl">📘</span>
                </div>
              <?php endif; ?>
              <div class="p-6 flex flex-col gap-3 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600"><?= e($course['category_name'] ?? 'Uncategorised') ?></p>
                <h2 class="text-lg font-bold text-gray-900 leading-snug"><?= e($course['title']) ?></h2>
                <?php if (!empty($course['description'])): ?>
                  <p class="text-sm text-gray-600 leading-relaxed line-clamp-3"><?= e($course['description']) ?></p>
                <?php endif; ?>
                <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                  <span class="text-sm font-black text-blue-900">
                    <?= (float) ($course['enrollment_fee_ksh'] ?? 0) > 0 ? 'Ksh ' . number_format((float) $course['enrollment_fee_ksh'], 2) : 'Free' ?>
                  </span>
                  <a href="<?= url('/account/register/choose') ?>" class="srms-btn-blue" style="padding:0.5rem 1rem;">Enroll</a>
                </div>
                <p class="text-xs text-gray-500">By <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['organisation_name'] ?? 'Tutor')) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-[#0f172a] text-white pt-16 pb-8 mt-auto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
      <div class="col-span-1 md:col-span-1">
        <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 mb-6">
          <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-600 text-white">
            🎓
          </div>
          <span class="font-serif-heading text-xl font-bold tracking-tight text-white"><?= e(appName()) ?>.</span>
        </a>
        <p class="text-gray-400 text-sm leading-relaxed mb-6">
          Empowering learners worldwide with quality education and practical skills for a better future.
        </p>
      </div>

      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Quick Links</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <li><a href="<?= url('/') ?>" class="hover:text-white transition">Home</a></li>
          <li><a href="<?= url('/courses') ?>" class="hover:text-white transition">Courses</a></li>
          <li><a href="<?= url('/account/register/choose') ?>" class="hover:text-white transition">Sign up</a></li>
          <li><a href="<?= url('/account/login') ?>" class="hover:text-white transition">Sign in</a></li>
        </ul>
      </div>

      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Support</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <li><a href="<?= url('/account/forgot-password') ?>" class="hover:text-white transition">Forgot password</a></li>
        </ul>
      </div>

      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Admin Tools</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <li><a href="<?= url('/admin/login') ?>" class="hover:text-white transition text-red-400">Super Admin</a></li>
        </ul>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
      <p>&copy; <?= date('Y') ?> <?= e(appName()) ?>. All Rights Reserved.</p>
      <p class="mt-4 md:mt-0">Made with ❤️ for Education</p>
    </div>
  </footer>
</body>
</html>
