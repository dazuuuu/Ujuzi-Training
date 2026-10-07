<?php
/**
 * A whole public page built from sections: head, navbar, the sections, footer.
 * Requires $pbPage and $pbTitle. Optional $pbDescription, $pbSlots, $pbExtraHead.
 */
use App\Services\PageBuilder;

$pbSite = PageBuilder::site();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
  <?php require __DIR__ . '/../../partials/pwa-head.php'; ?>
  <title><?= e($pbTitle) ?></title>
  <?php if (!empty($pbDescription)): ?><meta name="description" content="<?= e($pbDescription) ?>"/><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <style>
    html{-webkit-text-size-adjust:100%;-webkit-tap-highlight-color:transparent;}
    body{margin:0;font-family:'Inter',system-ui,sans-serif;background:#fff;color:#111827;overflow-x:hidden;}
  </style>
  <?php require __DIR__ . '/pb-styles.php'; ?>
  <?= $pbExtraHead ?? '' ?>
</head>
<body>
<?php require __DIR__ . '/public-nav.php'; ?>
<main>
  <?php require __DIR__ . '/pb-sections.php'; ?>
</main>
<?php require __DIR__ . '/public-footer.php'; ?>
</body>
</html>
