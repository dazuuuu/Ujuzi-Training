<?php
$side = $c['image_side'] ?? 'right';
$minHeight = (int) ($c['min_height'] ?? 0);
?>
<div class="pb-hero-grid<?= $side === 'left' ? ' is-left' : '' ?><?= $side === 'none' ? ' is-solo' : '' ?>" style="<?= $minHeight > 0 ? 'min-height:' . $minHeight . 'px;' : '' ?>">
  <div>
    <?php if (($c['badge'] ?? '') !== ''): ?><span class="pb-badge"><?= e($c['badge']) ?></span><?php endif; ?>
    <h1><?= e($c['title'] ?? '') ?><?php if (($c['title_highlight'] ?? '') !== ''): ?><em><?= e($c['title_highlight']) ?></em><?php endif; ?></h1>
    <?php if (($c['text'] ?? '') !== ''): ?><p class="pb-text" style="font-size:1.02em"><?= e($c['text']) ?></p><?php endif; ?>
    <?php require __DIR__ . '/_buttons.php'; ?>
  </div>
  <?php if ($side !== 'none'): ?>
    <div class="pb-hero-visual" aria-hidden="true">
      <?php if (($c['image'] ?? '') !== ''): ?>
        <img src="<?= e(imageUrl($c['image'])) ?>" alt="" onerror="this.remove()">
      <?php else: ?>
        <div class="pb-hero-empty"><?= icon('graduation', 'h-16 w-16') ?><?= e(appName()) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
