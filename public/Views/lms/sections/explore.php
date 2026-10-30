<?php require_once __DIR__ . '/_icon.php'; ?>
<div class="pbx-explore">
  <div>
    <?php if (($c['label'] ?? '') !== ''): ?><p class="pbx-label"><?= e($c['label']) ?></p><?php endif; ?>
    <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pbx-title"><?= e($c['title']) ?></h2><?php endif; ?>
    <div class="pbx-icon-cards">
      <?php foreach ($c['items'] ?? [] as $item): if (($item['title'] ?? '') === '') { continue; } ?>
        <div class="pbx-icon-card">
          <span class="pbx-icon-card-icon"><?= pbIcon((string) ($item['icon'] ?? 'star'), 34) ?></span>
          <h3><?= e($item['title']) ?></h3>
          <?php if (($item['text'] ?? '') !== ''): ?><p><?= e($item['text']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="pbx-round">
    <div class="pbx-round-pic"><?php if (($c['image'] ?? '') !== ''): ?><img src="<?= e(imageUrl($c['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="pbx-pic-empty"><?= pbIcon('users', 56) ?></div><?php endif; ?></div>
    <?php if (($c['badge_title'] ?? '') !== ''): ?>
      <div class="pbx-round-badge"><strong><?= e($c['badge_title']) ?></strong><?php if (($c['badge_text'] ?? '') !== ''): ?><span><?= e($c['badge_text']) ?></span><?php endif; ?></div>
    <?php endif; ?>
  </div>
</div>
