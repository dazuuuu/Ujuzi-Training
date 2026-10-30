<?php require_once __DIR__ . '/_icon.php'; ?>
<div class="pbx-strip">
  <?php foreach ($c['items'] ?? [] as $item): if (($item['title'] ?? '') === '') { continue; } ?>
    <div class="pbx-strip-item">
      <span class="pbx-strip-icon"><?= pbIcon((string) ($item['icon'] ?? 'star'), 30) ?></span>
      <div><strong><?= e($item['title']) ?></strong><?php if (($item['text'] ?? '') !== ''): ?><span><?= e($item['text']) ?></span><?php endif; ?></div>
    </div>
  <?php endforeach; ?>
</div>
