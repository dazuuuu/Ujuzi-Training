<?php require __DIR__ . '/_head.php'; ?>
<div class="pb-grid" style="--pb-cols:3">
  <?php foreach ($c['items'] ?? [] as $item): if (($item['value'] ?? '') === '') { continue; } ?>
    <div class="pb-card">
      <p style="font-size:.7em;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--pb-accent)"><?= e($item['label'] ?? '') ?></p>
      <h3><?php if (($item['link'] ?? '') !== ''): ?><a href="<?= $pbHref($item['link']) ?>" style="text-decoration:underline"><?= e($item['value']) ?></a><?php else: ?><?= e($item['value']) ?><?php endif; ?></h3>
    </div>
  <?php endforeach; ?>
</div>
