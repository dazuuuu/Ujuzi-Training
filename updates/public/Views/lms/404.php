<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Page Not Found | <?= e(appName()) ?></title>
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="min-h-screen flex items-center justify-center text-center p-6" style="background:var(--ke-black);color:var(--ke-white)">
  <div>
    <div class="flag-stripe mx-auto mb-8 max-w-xs rounded-full" aria-hidden="true"></div>
    <h1 class="font-serif-heading text-5xl font-bold mb-3">404</h1>
    <p class="text-sm mb-6" style="color:#b8e0cc">That page could not be found.</p>
    <a href="<?= url('/') ?>" class="btn-primary">Back to <?= e(appName()) ?></a>
  </div>
</body>
</html>
