<?php
/** Up to two buttons from $c: button_label/button_url and button2_label/button2_url. */
$pbButtons = [];
if (($c['button_label'] ?? '') !== '' && ($c['button_url'] ?? '') !== '') {
    $pbButtons[] = ['pb-btn-main', $c['button_label'], $c['button_url']];
}
if (($c['button2_label'] ?? '') !== '' && ($c['button2_url'] ?? '') !== '') {
    $pbButtons[] = ['pb-btn-alt', $c['button2_label'], $c['button2_url']];
}
?>
<?php if ($pbButtons): ?>
  <div class="pb-actions">
    <?php foreach ($pbButtons as [$pbBtnClass, $pbBtnLabel, $pbBtnUrl]): ?>
      <a href="<?= $pbHref($pbBtnUrl) ?>" class="pb-btn <?= $pbBtnClass ?>"><?= e($pbBtnLabel) ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
