<?php require __DIR__ . '/_head.php'; ?>
<div class="pb-grid" style="--pb-cols:<?= (int) ($c['columns'] ?? 3) ?: 3 ?>">
  <?php foreach ($c['items'] ?? [] as $item): ?>
    <div class="pb-card">
      <?php if (($item['icon'] ?? '') !== ''): ?><span class="pb-card-icon" aria-hidden="true"><?= e($item['icon']) ?></span><?php endif; ?>
      <?php if (($item['title'] ?? '') !== ''): ?><h3><?= e($item['title']) ?></h3><?php endif; ?>
      <?php if (($item['text'] ?? '') !== ''): ?><p><?= e($item['text']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
