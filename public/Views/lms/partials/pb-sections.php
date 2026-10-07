<?php
/**
 * Renders one public page's sections. Requires $pbPage. Optional $pbSlots:
 * type => callable(array $section) for sections the page draws itself (the
 * course catalog). Hidden sections are skipped, except in Super Admin's
 * preview, where they show faded so they can still be selected.
 */
use App\Services\PageBuilder;

$pbPreview = PageBuilder::previewToken() !== null;
$pbSlots = $pbSlots ?? [];
$pbHref = static fn(string $link): string => e(PageBuilder::href($link));
?>
<div class="pb<?= $pbPreview ? ' pb-previewing' : '' ?>">
<?php foreach (PageBuilder::sections($pbPage) as $pbIndex => $pbSection):
    $c = $pbSection['content'];
    $st = $pbSection['style'];
    if (!empty($st['hidden']) && !$pbPreview) {
        continue;
    }
    $bg = $st['bg'] ?? 'white';
    $isDark = in_array($bg, ['green', 'dark'], true);
    $inline = '';
    if ($bg === 'custom' && !empty($st['bg_color'])) {
        $inline .= 'background:' . $st['bg_color'] . ';';
    }
    if (!empty($st['text_color'])) {
        $inline .= 'color:' . $st['text_color'] . ';';
    }
    $classes = implode(' ', array_filter([
        'pb-sec',
        'pb-t-' . $pbSection['type'],
        'pb-bg-' . $bg,
        'pb-pad-' . ($st['padding'] ?? 'md'),
        'pb-align-' . ($st['align'] ?? 'left'),
        'pb-ts-' . ($st['text_size'] ?? 'md'),
        $isDark ? 'pb-dark' : '',
    ]));
    $wrap = 'pb-wrap pb-w-' . ($st['width'] ?? 'normal');
?>
  <section class="<?= $classes ?>" <?= !empty($st['anchor']) ? 'id="' . e(preg_replace('/[^a-z0-9-]/', '', strtolower($st['anchor']))) . '"' : '' ?> data-pb-index="<?= (int) $pbIndex ?>" <?= !empty($st['hidden']) ? 'data-pb-hidden' : '' ?> style="<?= e($inline) ?>">
    <?php if (isset($pbSlots[$pbSection['type']])): ?>
      <?php $pbSlots[$pbSection['type']]($pbSection); ?>
    <?php else: ?>
      <div class="<?= $wrap ?>">
        <?php require __DIR__ . '/../sections/' . $pbSection['type'] . '.php'; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
</div>
<?php if ($pbPreview): ?>
<script>
  // Tell the editor which section was clicked, and follow its selection.
  document.addEventListener('click', function (event) {
    var sec = event.target.closest('[data-pb-index]');
    if (!sec) return;
    event.preventDefault();
    parent.postMessage({ pbSelect: +sec.getAttribute('data-pb-index') }, location.origin);
  }, true);
  window.addEventListener('message', function (event) {
    if (event.origin !== location.origin || typeof event.data.pbSelected !== 'number') return;
    document.querySelectorAll('.pb-selected').forEach(function (el) { el.classList.remove('pb-selected'); });
    var sec = document.querySelector('[data-pb-index="' + event.data.pbSelected + '"]');
    if (sec) { sec.classList.add('pb-selected'); sec.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
  });
</script>
<?php endif; ?>
