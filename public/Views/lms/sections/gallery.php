<?php require_once __DIR__ . '/_icon.php'; ?>
<div class="pbx-gallery-head">
  <div>
    <?php if (($c['label'] ?? '') !== ''): ?><p class="pbx-label"><?= e($c['label']) ?></p><?php endif; ?>
    <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pbx-title"><?= e($c['title']) ?></h2><?php endif; ?>
  </div>
  <?php if (($c['button_label'] ?? '') !== ''): ?><a href="<?= $pbHref($c['button_url'] ?? '/') ?>" class="pbx-btn is-outline"><?= e($c['button_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a><?php endif; ?>
</div>
<div class="pbx-gallery">
  <?php foreach ($c['items'] ?? [] as $item): ?>
    <figure>
      <?php if (($item['image'] ?? '') !== ''): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt="<?= e($item['caption'] ?? '') ?>" loading="lazy"><?php else: ?><div class="pbx-pic-empty"><?= pbIcon('star', 32) ?></div><?php endif; ?>
      <?php if (($item['caption'] ?? '') !== ''): ?><figcaption><?= e($item['caption']) ?></figcaption><?php endif; ?>
    </figure>
  <?php endforeach; ?>
</div>
