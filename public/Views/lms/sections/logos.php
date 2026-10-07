<?php $logoList = \App\Services\HomepageContent::logos(); ?>
<?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title" style="margin-bottom:1.2em"><?= e($c['title']) ?></h2><?php endif; ?>
<div class="pb-logos" style="--pb-logo-h:<?= (int) ($c['logo_height'] ?? 42) ?: 42 ?>px">
  <?php if ($logoList): ?>
    <?php foreach ($logoList as $logo): ?>
      <img src="<?= e(imageUrl($logo['image'])) ?>" alt="<?= e($logo['name'] ?: 'Partner logo') ?>" title="<?= e($logo['name'] ?? '') ?>" loading="lazy">
    <?php endforeach; ?>
  <?php else: ?>
    <?php foreach (\App\Services\HomepageContent::DEFAULT_LOGO_NAMES as $logoName): ?><span><?= e($logoName) ?></span><?php endforeach; ?>
  <?php endif; ?>
</div>
