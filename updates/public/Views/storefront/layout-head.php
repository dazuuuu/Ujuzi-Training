<?php
/**
 * Requires $seo (title/description/keywords/image/tags — resolved by
 * SeoMeta::resolve() in StorefrontController, editable at /admin/seo).
 */
$ogImage = !empty($seo['image']) ? imageUrl($seo['image']) : null;
$tagList = !empty($seo['tags']) ? array_filter(array_map('trim', explode(',', $seo['tags']))) : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>" />
  <?php if (!empty($seo['keywords'])): ?><meta name="keywords" content="<?= e($seo['keywords']) ?>" /><?php endif; ?>
  <meta property="og:title" content="<?= e($seo['title']) ?>" />
  <meta property="og:description" content="<?= e($seo['description']) ?>" />
  <?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>" /><?php endif; ?>
  <?php foreach ($tagList as $tag): ?><meta property="article:tag" content="<?= e($tag) ?>" /><?php endforeach; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-white text-[#1a1a1a] antialiased selection:bg-[#1a1a1a] selection:text-white">
  <div id="app" class="min-h-screen flex flex-col bg-white text-[#0a0a0a] font-sans antialiased">
