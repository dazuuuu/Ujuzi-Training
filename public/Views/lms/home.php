<?php
/** Public LMS landing. Requires $roles, $needsSetup. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title><?= e(appName()) ?> | Learning Management System</title>
  <meta name="description" content="Role-based learning platform for organisation admins, tutors, attachment providers, and students." />
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
        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-600 text-white shadow-sm shrink-0">
          🎓
        </div>
        <div class="flex flex-col text-left leading-none">
          <span class="font-serif-heading text-xl font-bold tracking-tight text-gray-900"><?= e(appName()) ?>.</span>
        </div>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-700">
        <a href="<?= url('/') ?>" class="text-green-600 border-b-2 border-green-600 pb-1">Home</a>
        <a href="<?= url('/courses') ?>" class="hover:text-green-600 transition pb-1">Courses</a>
        <a href="<?= url('/account/login') ?>" class="hover:text-green-600 transition pb-1">Sign in</a>
        <a href="<?= url('/account/register/choose') ?>" class="hover:text-green-600 transition pb-1">Sign up</a>
      </nav>
      <div class="flex items-center gap-4">
        <a href="<?= url('/account/register/choose') ?>" class="srms-btn-red hidden sm:inline-flex">Enroll Now</a>
        <button class="md:hidden text-gray-700 text-2xl" id="publicMenuToggle">☰</button>
      </div>
    </div>
  </header>
  
  <!-- Public Mobile Menu -->
  <div id="publicMobileMenu" class="hidden md:hidden bg-white border-b border-gray-100 shadow-lg px-4 py-4 space-y-3 absolute w-full z-40">
    <a href="<?= url('/') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Home</a>
    <a href="<?= url('/courses') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Courses</a>
    <a href="<?= url('/account/login') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Sign in</a>
    <a href="<?= url('/account/register/choose') ?>" class="block font-semibold text-gray-700 hover:text-green-600">Sign up</a>
    <a href="<?= url('/account/register/choose') ?>" class="block srms-btn-red w-full text-center mt-4">Enroll Now</a>
  </div>
  
  <script>
    const publicMenuToggle = document.getElementById('publicMenuToggle');
    const publicMobileMenu = document.getElementById('publicMobileMenu');
    if (publicMenuToggle && publicMobileMenu) {
      publicMenuToggle.addEventListener('click', function() {
        publicMobileMenu.classList.toggle('hidden');
      });
    }
  </script>

  <!-- Hero Section -->
  <section class="srms-hero-section">
    <div class="srms-hero-blob-left hidden lg:block"></div>
    <div class="srms-hero-blob-right hidden lg:block"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid lg:grid-cols-2 gap-12 items-center srms-hero-content">
      <div class="space-y-6">
        <div class="inline-block bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase">
          Best Online Education
        </div>
        <h1 class="text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight">
          Build Your Future<br>
          <span class="text-gray-900">With <?= e(appName()) ?>.</span>
        </h1>
        <p class="text-gray-600 text-lg max-w-lg leading-relaxed">
          Join thousands of learners worldwide and gain skills that shape your future with expert-led online courses and comprehensive learning paths.
        </p>
        <div class="flex flex-wrap gap-4 pt-4">
          <a href="<?= url('/courses') ?>" class="srms-btn-green">Explore Courses</a>
          <a href="<?= url('/account/register/choose') ?>" class="srms-btn-green-outline">Learn More</a>
        </div>
      </div>
      
      <div class="relative hidden lg:block">
        <div class="rounded-3xl h-[500px] w-full flex items-center justify-center relative overflow-hidden shadow-2xl border-4 border-white bg-gradient-to-br from-green-800 via-green-600 to-green-500">
          <span class="text-white/90 text-8xl">🎓</span>

          <!-- Floating Stat Badge 1 -->
          <div class="absolute top-10 -left-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
            <div class="bg-green-50 text-green-700 p-2 rounded-lg">🎓</div>
            <div>
              <div class="font-black text-xl text-gray-900">20K+</div>
              <div class="text-[10px] uppercase font-bold text-gray-500">Active Students</div>
            </div>
          </div>
          
          <!-- Floating Stat Badge 2 -->
          <div class="absolute bottom-20 -right-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
            <div class="bg-red-50 text-red-700 p-2 rounded-lg">⭐</div>
            <div>
              <div class="font-black text-xl text-gray-900">95%</div>
              <div class="text-[10px] uppercase font-bold text-gray-500">Success Rate</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Roles / Categories Section -->
  <section class="py-20 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Platform Roles</h2>
        <p class="text-gray-600">Discover our tailored dashboards designed to help you learn, teach, or manage with precision and ease.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-center">
        <?php
        $fallbackRoles = [
            ['slug' => 'student', 'name' => 'Student', 'description' => 'Completes assigned profile forms and enrolls.', 'has_admin_features' => false],
            ['slug' => 'trainer', 'name' => 'Tutor', 'description' => 'Under organisation power. Dashboard and profile only.', 'has_admin_features' => false],
            ['slug' => 'organisation_admin', 'name' => 'Organisations providing courses', 'description' => 'Admin-like tools inside an organisation.', 'has_admin_features' => true],
            ['slug' => 'attachment_trainer', 'name' => 'Organisation providing Attachment', 'description' => 'Shown to students after they finish a course.', 'has_admin_features' => false],
        ];
        $cards = $roles ?: $fallbackRoles;
        
        // Add Super Admin explicitly if not already present
        $hasSuperAdmin = false;
        foreach ($cards as $c) {
            if (($c['slug'] ?? '') === 'super_admin') $hasSuperAdmin = true;
        }
        if (!$hasSuperAdmin) {
            array_unshift($cards, [
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Platform owner. Builds roles, forms, and system-wide settings.',
                'has_admin_features' => true,
            ]);
        }
        
        $icons = ['👑', '🎓', '👨‍🏫', '🏢', '🤝', '⚡'];
        foreach ($cards as $idx => $role):
          $adminLike = !empty($role['has_admin_features']);
          $loginHref = ($role['slug'] === 'super_admin') 
              ? url('/admin/login') 
              : (!empty($role['slug']) ? url(\App\Core\LoginRoles::loginPath((string) $role['slug'])) : url('/account/login'));
        ?>
          <a href="<?= $loginHref ?>" class="srms-feature-card group flex-col text-center hover:bg-green-50">
            <div class="srms-feature-icon transition-transform group-hover:scale-110 group-hover:bg-green-600 group-hover:text-white">
              <?= $icons[$idx % count($icons)] ?>
            </div>
            <div>
              <h3 class="font-bold text-lg text-gray-900"><?= e($role['name']) ?></h3>
              <p class="text-sm text-gray-500 mt-2"><?= e($role['description'] ?? '') ?></p>
            </div>
            <div class="mt-4 text-xs font-bold uppercase tracking-widest text-green-600 group-hover:text-green-800">
              Sign In &rarr;
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA Bar -->
  <section class="bg-gray-900 text-white py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-gray-800">
      <div class="pt-8 md:pt-0">
        <div class="text-4xl font-extrabold mb-2">20,000+</div>
        <div class="text-red-400 text-sm font-semibold uppercase tracking-wider">Active Students</div>
      </div>
      <div class="pt-8 md:pt-0">
        <div class="text-4xl font-extrabold mb-2">500+</div>
        <div class="text-red-400 text-sm font-semibold uppercase tracking-wider">Expert Instructors</div>
      </div>
      <div class="pt-8 md:pt-0">
        <div class="text-4xl font-extrabold mb-2">1,200+</div>
        <div class="text-red-400 text-sm font-semibold uppercase tracking-wider">Courses Available</div>
      </div>
      <div class="pt-8 md:pt-0">
        <div class="text-4xl font-extrabold mb-2">95%</div>
        <div class="text-red-400 text-sm font-semibold uppercase tracking-wider">Success Rate</div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-[#0f172a] text-white pt-16 pb-8 mt-auto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
      <div class="col-span-1 md:col-span-1">
        <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 mb-6">
          <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-600 text-white">
            🎓
          </div>
          <span class="font-serif-heading text-xl font-bold tracking-tight text-white"><?= e(appName()) ?>.</span>
        </a>
        <p class="text-gray-400 text-sm leading-relaxed mb-6">
          Empowering learners worldwide with quality education and practical skills for a better future.
        </p>
        <div class="flex gap-4">
          <a href="#" class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center hover:bg-green-600 transition">f</a>
          <a href="#" class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center hover:bg-green-600 transition">t</a>
          <a href="#" class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center hover:bg-green-600 transition">in</a>
        </div>
      </div>
      
      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Quick Links</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <li><a href="<?= url('/') ?>" class="hover:text-white transition">About Us</a></li>
          <li><a href="<?= url('/courses') ?>" class="hover:text-white transition">Courses</a></li>
          <li><a href="#" class="hover:text-white transition">Events</a></li>
          <li><a href="#" class="hover:text-white transition">Blog</a></li>
          <li><a href="#" class="hover:text-white transition">Contact Us</a></li>
        </ul>
      </div>
      
      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Support</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <li><a href="#" class="hover:text-white transition">Help Center</a></li>
          <li><a href="#" class="hover:text-white transition">Terms & Conditions</a></li>
          <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
          <li><a href="#" class="hover:text-white transition">Refund Policy</a></li>
        </ul>
      </div>

      <div>
        <h4 class="font-bold text-lg mb-6 text-white">Admin Tools</h4>
        <ul class="space-y-3 text-sm text-gray-400">
          <?php if (!empty($needsSetup)): ?>
            <li><a href="<?= url('/setup') ?>" class="hover:text-white transition text-red-400">Run first-time setup</a></li>
          <?php else: ?>
            <li><a href="<?= url('/admin/login') ?>" class="hover:text-white transition text-red-400">Super Admin</a></li>
          <?php endif; ?>
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
