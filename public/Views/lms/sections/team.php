<?php require __DIR__ . '/_head.php'; ?>
<div class="pb-team">
  <?php foreach ($c['items'] ?? [] as $item): ?>
    <div class="pb-person">
      <?php if (($item['photo'] ?? '') !== ''): ?>
        <img src="<?= e(imageUrl($item['photo'])) ?>" alt="" loading="lazy">
      <?php else: ?>
        <span class="pb-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim((string) ($item['name'] ?? '?')), 0, 1))) ?></span>
      <?php endif; ?>
      <strong><?= e($item['name'] ?? '') ?></strong>
      <span><?= e($item['role'] ?? '') ?></span>
    </div>
  <?php endforeach; ?>
</div>
