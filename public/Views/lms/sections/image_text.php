<?php $imgHeight = (int) ($c['image_height'] ?? 0) ?: 360; ?>
<div class="pb-split<?= ($c['image_side'] ?? 'left') === 'right' ? ' is-right' : '' ?>">
  <?php if (($c['image'] ?? '') !== ''): ?>
    <img class="pb-split-img" src="<?= e(imageUrl($c['image'])) ?>" alt="" style="height:<?= $imgHeight ?>px" loading="lazy">
  <?php else: ?>
    <div class="pb-split-img" style="height:<?= $imgHeight ?>px" aria-hidden="true"></div>
  <?php endif; ?>
  <div>
    <?php require __DIR__ . '/_head.php'; ?>
    <?php require __DIR__ . '/_buttons.php'; ?>
  </div>
</div>
