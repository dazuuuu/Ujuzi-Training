<?php
/**
 * Visual reports: one card per chart from ReportCharts::build(). Requires
 * $reportCharts. Bars, lines and donuts are inline SVG; hovering a mark shows
 * its exact value and every chart also prints its values, so nothing relies
 * on colour alone. Single-series charts use one colour; the donuts use the
 * validated categorical order.
 */
$rcSeries = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#4a3aa7'];
$rcBrand = '#006b3f';
$rcFmt = static fn(float $v, string $unit): string => ($unit === 'Ksh' ? 'Ksh ' : '') . number_format($v);
$rcShort = static function (float $v, string $unit): string {
    $s = $v >= 1000000 ? round($v / 1000000, 1) . 'M' : ($v >= 1000 ? round($v / 1000, 1) . 'k' : (string) round($v));
    return ($unit === 'Ksh' ? 'Ksh ' : '') . $s;
};
/** A clean axis top: 4 steps of 1, 2, 2.5 or 5 × 10ⁿ. */
$rcTop = static function (float $max): float {
    if ($max <= 0) {
        return 4;
    }
    $raw = $max / 4;
    $mag = 10 ** floor(log10($raw));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($m * $mag >= $raw) {
            return $m * $mag * 4;
        }
    }
    return $max;
};
?>
<style>
  .rc-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(min(100%,420px),1fr));}
  .rc-card{min-width:0;padding:18px 20px;border-radius:18px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(15,23,42,.06);}
  .rc-card h3{font-size:.85rem;font-weight:800;color:#1f2937;margin-bottom:12px;}
  .rc-axis{font-size:9px;font-weight:700;fill:#6b7280;}
  .rc-grid-line{stroke:#eceae4;stroke-width:1;}
  .rc-hit{cursor:default;}
  .rc-hit:hover .rc-bar{filter:brightness(.88);}
  .rc-hit .rc-dot{opacity:0;}
  .rc-hit:hover .rc-dot{opacity:1;}
  .rc-hbar{display:grid;grid-template-columns:minmax(0,10rem) 1fr auto;align-items:center;gap:10px;font-size:.78rem;font-weight:700;color:#374151;}
  .rc-track{height:10px;border-radius:999px;background:#f1f2ef;overflow:hidden;}
  .rc-fill{display:block;height:100%;border-radius:999px;}
  .rc-legend{display:flex;flex-direction:column;gap:8px;font-size:.78rem;font-weight:700;color:#374151;min-width:11rem;flex:1;}
  .rc-legend li{display:flex;align-items:center;gap:8px;}
  .rc-legend .n{margin-left:auto;font-weight:900;color:#111827;}
  .rc-swatch{width:10px;height:10px;border-radius:3px;flex-shrink:0;}
  .rc-empty{font-size:.8rem;font-weight:700;color:#6b7280;}
  .rc-total{font-size:.72rem;font-weight:700;color:#6b7280;margin-top:8px;}
  #rc-tip{position:fixed;z-index:80;pointer-events:none;padding:6px 9px;border-radius:8px;background:#111827;color:#fff;font-size:.72rem;font-weight:700;white-space:nowrap;opacity:0;}
</style>

<div class="rc-grid">
  <?php foreach ($reportCharts as $chart):
    $points = $chart['points']; $unit = $chart['unit']; $values = array_values($points); $labels = array_keys($points);
    $sum = array_sum($values);
  ?>
    <article class="rc-card">
      <h3><?= e($chart['title']) ?></h3>
      <?php if (!$points || $sum <= 0): ?>
        <p class="rc-empty">No data for this period yet.</p>
      <?php elseif ($chart['type'] === 'hbar'): $max = max($values); ?>
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php foreach ($points as $label => $v): ?>
            <div class="rc-hbar rc-hit" data-tip="<?= e($label . ': ' . $rcFmt($v, $unit)) ?>">
              <span class="truncate" title="<?= e($label) ?>"><?= e($label) ?></span>
              <span class="rc-track"><span class="rc-fill" style="width:<?= max(3, round($v / $max * 100)) ?>%;background:<?= $rcBrand ?>"></span></span>
              <span><?= e($rcShort($v, $unit)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($chart['type'] === 'donut'):
        $r = 56; $len = 2 * M_PI * $r; $offset = 0;
        $slices = array_filter($points, static fn($v) => $v > 0);
      ?>
        <div class="flex flex-wrap items-center gap-6">
          <svg viewBox="0 0 150 150" width="140" height="140" role="img" aria-label="<?= e($chart['title']) ?>">
            <?php $i = 0; foreach ($points as $label => $v): $color = $rcSeries[$i++ % 6]; if ($v <= 0) { continue; }
              $seg = $len * $v / $sum; $gap = count($slices) > 1 ? min(2, $seg / 2) : 0; ?>
              <g class="rc-hit" data-tip="<?= e($label . ': ' . $rcFmt($v, $unit) . ' (' . round($v / $sum * 100) . '%)') ?>">
                <circle cx="75" cy="75" r="<?= $r ?>" fill="none" stroke="<?= $color ?>" stroke-width="22"
                        stroke-dasharray="<?= round(max(0, $seg - $gap), 2) ?> <?= round($len, 2) ?>" stroke-dashoffset="<?= round(-$offset, 2) ?>" transform="rotate(-90 75 75)"/>
              </g>
            <?php $offset += $seg; endforeach; ?>
            <text x="75" y="76" text-anchor="middle" style="font-size:20px;font-weight:900;fill:#111827"><?= e($rcShort($sum, $unit)) ?></text>
            <text x="75" y="92" text-anchor="middle" class="rc-axis">total</text>
          </svg>
          <ul class="rc-legend">
            <?php $i = 0; foreach ($points as $label => $v): ?>
              <li><span class="rc-swatch" style="background:<?= $rcSeries[$i++ % 6] ?>"></span><?= e($label) ?><span class="n"><?= e($rcFmt($v, $unit)) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else:
        // Vertical bars or an area line over time, one axis.
        $W = 460; $H = 200; $L = 44; $B = 22; $top = $rcTop(max($values)); $n = count($values);
        $bw = ($W - $L) / max(1, $n); $plotH = $H - $B - 10;
        $xAt = static fn(int $i): float => $L + $i * $bw + $bw / 2;
        $yAt = static fn(float $v): float => $H - $B - $plotH * $v / $top;
        $every = (int) max(1, ceil($n / 8)); // thin the month labels when there are many
        $best = array_search(max($values), $values, true);
      ?>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full" style="height:auto;max-height:240px" role="img" aria-label="<?= e($chart['title']) ?>">
          <?php for ($g = 0; $g <= 4; $g++): $gy = $H - $B - $plotH * $g / 4; ?>
            <line x1="<?= $L ?>" x2="<?= $W ?>" y1="<?= $gy ?>" y2="<?= $gy ?>" class="rc-grid-line"/>
            <text x="<?= $L - 6 ?>" y="<?= $gy + 3 ?>" text-anchor="end" class="rc-axis"><?= e($rcShort($top * $g / 4, '')) ?></text>
          <?php endfor; ?>
          <?php if ($chart['type'] === 'line'):
            $line = implode(' ', array_map(static fn($i) => round($xAt($i), 1) . ',' . round($yAt($values[$i]), 1), array_keys($values)));
          ?>
            <polygon points="<?= round($xAt(0), 1) ?>,<?= $H - $B ?> <?= $line ?> <?= round($xAt($n - 1), 1) ?>,<?= $H - $B ?>" fill="<?= $rcBrand ?>" fill-opacity=".12"/>
            <polyline points="<?= $line ?>" fill="none" stroke="<?= $rcBrand ?>" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
          <?php endif; ?>
          <?php foreach ($values as $i => $v): $x = $xAt($i); $y = $yAt($v); ?>
            <g class="rc-hit" data-tip="<?= e($labels[$i] . ': ' . $rcFmt($v, $unit)) ?>">
              <rect x="<?= round($L + $i * $bw, 1) ?>" y="0" width="<?= round($bw, 1) ?>" height="<?= $H - $B ?>" fill="transparent"/>
              <?php if ($chart['type'] === 'bar' && $v > 0): $w = min(28, $bw * 0.55); ?>
                <rect class="rc-bar" x="<?= round($x - $w / 2, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($w, 1) ?>" height="<?= round($H - $B - $y, 1) ?>" rx="4" fill="<?= $rcBrand ?>"/>
              <?php elseif ($chart['type'] === 'line'): ?>
                <circle class="rc-dot" cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="4" fill="<?= $rcBrand ?>" stroke="#fff" stroke-width="2"/>
              <?php endif; ?>
              <?php if ($i === $best && $v > 0): ?><text x="<?= round($x, 1) ?>" y="<?= round($y - 6, 1) ?>" text-anchor="middle" style="font-size:10px;font-weight:900;fill:#111827"><?= e($rcShort($v, $unit)) ?></text><?php endif; ?>
            </g>
            <?php if ($i % $every === 0): ?><text x="<?= round($x, 1) ?>" y="<?= $H - 6 ?>" text-anchor="middle" class="rc-axis"><?= e($labels[$i]) ?></text><?php endif; ?>
          <?php endforeach; ?>
        </svg>
        <p class="rc-total">Total: <?= e($rcFmt($sum, $unit)) ?></p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  <?php if (!$reportCharts): ?><p class="rc-empty">No report data yet.</p><?php endif; ?>
</div>
<div id="rc-tip" role="tooltip"></div>
<script>
(function () {
  var tip = document.getElementById('rc-tip');
  document.querySelectorAll('.rc-hit[data-tip]').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      tip.textContent = el.getAttribute('data-tip');
      tip.style.opacity = 1;
      tip.style.left = Math.min(e.clientX + 12, window.innerWidth - tip.offsetWidth - 8) + 'px';
      tip.style.top = (e.clientY - tip.offsetHeight - 10) + 'px';
    });
    el.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
  });
})();
</script>
