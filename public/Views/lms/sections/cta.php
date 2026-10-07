<div class="pb-cta">
  <?php if (($c['title'] ?? '') !== ''): ?><h2 class="pb-title"><?= e($c['title']) ?></h2><?php endif; ?>
  <?php if (($c['text'] ?? '') !== ''): ?><p class="pb-text"><?= e($c['text']) ?></p><?php endif; ?>
  <?php require __DIR__ . '/_buttons.php'; ?>
  <?php if (($c['note'] ?? '') !== '' || ($c['note_link_label'] ?? '') !== ''): ?>
    <span class="pb-note"><?= e($c['note'] ?? '') ?>
      <?php if (($c['note_link_label'] ?? '') !== '' && ($c['note_link_url'] ?? '') !== ''): ?> <a href="<?= $pbHref($c['note_link_url']) ?>"><?= e($c['note_link_label']) ?></a><?php endif; ?>
    </span>
  <?php endif; ?>
</div>
