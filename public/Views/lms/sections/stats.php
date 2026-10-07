<?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title" style="margin-bottom:1em"><?= e($c['title']) ?></h2><?php endif; ?>
<div class="pb-stats">
  <?php foreach ($c['items'] ?? [] as $item): ?>
    <div class="pb-stat"><strong><?= e($item['value'] ?? '') ?></strong><span><?= e($item['label'] ?? '') ?></span></div>
  <?php endforeach; ?>
</div>
