<?php require_once __DIR__ . '/_icon.php'; ?>
<?php if (($c['label'] ?? '') !== ''): ?><p class="pbx-label"><?= e($c['label']) ?></p><?php endif; ?>
<?php if (($c['title'] ?? '') !== ''): ?><h2 class="pbx-title"><?= e($c['title']) ?></h2><?php endif; ?>
<div class="pbx-pic-cards">
  <?php foreach ($c['items'] ?? [] as $item): if (($item['title'] ?? '') === '') { continue; } $tag = ($item['url'] ?? '') !== '' ? 'a' : 'div'; ?>
    <<?= $tag ?> class="pbx-pic-card" <?= $tag === 'a' ? 'href="' . $pbHref($item['url']) . '"' : '' ?>>
      <div class="pbx-pic-card-img"><?php if (($item['image'] ?? '') !== ''): ?><img src="<?= e(imageUrl($item['image'])) ?>" alt="" loading="lazy"><?php else: ?><div class="pbx-pic-empty"><?= pbIcon((string) ($item['icon'] ?? 'star'), 44) ?></div><?php endif; ?></div>
      <span class="pbx-pic-card-icon"><?= pbIcon((string) ($item['icon'] ?? 'star'), 20) ?></span>
      <div class="pbx-pic-card-body"><h3><?= e($item['title']) ?></h3><?php if (($item['text'] ?? '') !== ''): ?><p><?= e($item['text']) ?></p><?php endif; ?></div>
    </<?= $tag ?>>
  <?php endforeach; ?>
</div>
<?php if (($c['button_label'] ?? '') !== ''): ?><div class="pbx-center"><a href="<?= $pbHref($c['button_url'] ?? '/') ?>" class="pbx-btn is-outline"><?= e($c['button_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a></div><?php endif; ?>
