<?php require_once __DIR__ . '/_icon.php'; ?>
<div class="pbx-band">
  <span class="pbx-band-icon"><?= pbIcon((string) ($c['icon'] ?? 'cap'), 54) ?></span>
  <h2><?= nl2br(e($c['title'] ?? '')) ?></h2>
  <?php if (($c['text'] ?? '') !== ''): ?><p><?= e($c['text']) ?></p><?php endif; ?>
  <?php if (($c['button_label'] ?? '') !== ''): ?><a href="<?= $pbHref($c['button_url'] ?? '/') ?>" class="pbx-btn is-gold"><?= e($c['button_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a><?php endif; ?>
</div>
