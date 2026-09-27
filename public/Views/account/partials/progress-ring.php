<?php
/**
 * A small circular progress indicator with the percentage in the middle.
 * Requires $pct (0–100). Optional $ringSize (px, default 40) and $ringLabel
 * (what the percentage is of, for screen readers).
 */
$ringPct = max(0, min(100, (int) ($pct ?? 0)));
$ringSize = (int) ($ringSize ?? 40);
$ringStroke = max(3, (int) round($ringSize / 10));
$ringR = ($ringSize - $ringStroke) / 2;
$ringC = 2 * M_PI * $ringR;
$ringColor = $ringPct >= 100 ? 'var(--ke-green)' : ($ringPct >= 50 ? '#16a34a' : ($ringPct > 0 ? '#f59e0b' : '#d4d4d4'));
?>
<span class="progress-ring" role="progressbar" aria-valuenow="<?= $ringPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e($ringLabel ?? 'Progress') ?>: <?= $ringPct ?>%"
      style="width:<?= $ringSize ?>px;height:<?= $ringSize ?>px">
  <svg width="<?= $ringSize ?>" height="<?= $ringSize ?>" viewBox="0 0 <?= $ringSize ?> <?= $ringSize ?>" aria-hidden="true">
    <circle cx="<?= $ringSize / 2 ?>" cy="<?= $ringSize / 2 ?>" r="<?= $ringR ?>" fill="none" stroke="#e5e7eb" stroke-width="<?= $ringStroke ?>"/>
    <circle cx="<?= $ringSize / 2 ?>" cy="<?= $ringSize / 2 ?>" r="<?= $ringR ?>" fill="none" stroke="<?= $ringColor ?>" stroke-width="<?= $ringStroke ?>"
            stroke-linecap="round" stroke-dasharray="<?= round($ringC, 2) ?>" stroke-dashoffset="<?= round($ringC * (1 - $ringPct / 100), 2) ?>"
            transform="rotate(-90 <?= $ringSize / 2 ?> <?= $ringSize / 2 ?>)"/>
  </svg>
  <span class="progress-ring-value" style="font-size:<?= max(9, (int) round($ringSize / 4)) ?>px"><?= $ringPct ?>%</span>
</span>
<?php unset($ringLabel, $ringSize); ?>
