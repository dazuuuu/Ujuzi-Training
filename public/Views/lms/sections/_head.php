<?php /** Label, heading and text from $c, when set. */ ?>
<?php if (($c['label'] ?? '') !== '' || ($c['title'] ?? '') !== '' || ($c['text'] ?? '') !== ''): ?>
  <div class="pb-head">
    <?php if (($c['label'] ?? '') !== ''): ?><p class="pb-label"><?= e($c['label']) ?></p><?php endif; ?>
    <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title"><?= e($c['title']) ?></h2><?php endif; ?>
    <?php if (($c['text'] ?? '') !== ''): ?><p class="pb-text"><?= e($c['text']) ?></p><?php endif; ?>
  </div>
<?php endif; ?>
