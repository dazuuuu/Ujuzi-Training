<?php
/** Full-width picture slider: heading with highlighted words, text, two buttons, arrows and dots. */
$slides = array_values(array_filter($c['slides'] ?? [], static fn(array $sl): bool => ($sl['title'] ?? '') !== '' || ($sl['image'] ?? '') !== ''));
$slides = $slides ?: [['image' => '', 'title' => appName(), 'title_highlight' => '', 'text' => '']];
$height = (int) ($c['height'] ?? 0) ?: 560;
$sliderId = 'pbs-' . substr(md5(json_encode($c)), 0, 6);
?>
<div class="pbx-slider" id="<?= $sliderId ?>" style="--pbx-h:<?= $height ?>px" data-seconds="<?= max(3, (int) ($c['seconds'] ?? 6)) ?>">
  <?php foreach ($slides as $i => $slide): ?>
    <div class="pbx-slide<?= $i === 0 ? ' is-active' : '' ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
      <?php if (($slide['image'] ?? '') !== ''): ?><img src="<?= e(imageUrl($slide['image'])) ?>" alt="" <?= $i === 0 ? '' : 'loading="lazy"' ?>><?php endif; ?>
      <div class="pbx-slide-inner">
        <div class="pbx-slide-copy">
          <h1><?= e($slide['title'] ?? '') ?><?php if (($slide['title_highlight'] ?? '') !== ''): ?> <em><?= e($slide['title_highlight']) ?></em><?php endif; ?></h1>
          <?php if (($slide['text'] ?? '') !== ''): ?><p><?= e($slide['text']) ?></p><?php endif; ?>
          <div class="pbx-slide-actions">
            <?php if (($c['button_label'] ?? '') !== ''): ?><a href="<?= $pbHref($c['button_url'] ?? '/') ?>" class="pbx-btn is-gold"><?= e($c['button_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a><?php endif; ?>
            <?php if (($c['button2_label'] ?? '') !== ''): ?><a href="<?= $pbHref($c['button2_url'] ?? '/') ?>" class="pbx-btn is-ghost"><?= e($c['button2_label']) ?> <?php require __DIR__ . '/_arrow.php'; ?></a><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (count($slides) > 1): ?>
    <button type="button" class="pbx-arrow is-prev" aria-label="Previous slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
    <button type="button" class="pbx-arrow is-next" aria-label="Next slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
    <div class="pbx-dots"><?php foreach ($slides as $i => $_): ?><button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach; ?></div>
  <?php endif; ?>
</div>
<?php if (count($slides) > 1): ?>
<script>
(function () {
  var root = document.getElementById('<?= $sliderId ?>'), slides = root.querySelectorAll('.pbx-slide'), dots = root.querySelectorAll('.pbx-dots button'), at = 0, timer;
  var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(n) {
    at = (n + slides.length) % slides.length;
    slides.forEach(function (s, i) { s.classList.toggle('is-active', i === at); s.setAttribute('aria-hidden', i === at ? 'false' : 'true'); });
    dots.forEach(function (d, i) { d.classList.toggle('is-active', i === at); });
  }
  function play() { clearInterval(timer); if (!still) timer = setInterval(function () { show(at + 1); }, root.dataset.seconds * 1000); }
  root.querySelector('.is-prev').addEventListener('click', function () { show(at - 1); play(); });
  root.querySelector('.is-next').addEventListener('click', function () { show(at + 1); play(); });
  dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); play(); }); });
  var x0 = null;
  root.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
  root.addEventListener('touchend', function (e) { if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) { show(at + (dx < 0 ? 1 : -1)); play(); } x0 = null; });
  play();
})();
</script>
<?php endif; ?>
