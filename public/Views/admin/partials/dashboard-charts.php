<?php
/**
 * The Super Admin dashboard's chart cards. Requires $charts (see
 * Admin\DashboardController::charts()), $roleCounts, $recentUsers and
 * $attachmentStats. Charts are inline SVG drawn here; hovering a mark shows
 * its exact value, and every chart also prints its values as text.
 */
$c = $charts ?? [];
$fmt = static fn(float $n): string => number_format($n);
$ksh = static fn(float $n): string => 'Ksh ' . number_format($n);

// Reference categorical palette (validated, light surface), fixed order.
$series = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#4a3aa7'];
$brand = '#006b3f';

/** A 7-point area sparkline with a hover target per point. */
$spark = static function (array $points, string $color, callable $label) : string {
    $w = 280; $h = 74; $pad = 6;
    $values = array_values($points);
    $keys = array_keys($points);
    $max = max(1, max($values));
    $step = ($w - $pad * 2) / max(1, count($values) - 1);
    $xy = [];
    foreach ($values as $i => $v) {
        $xy[] = [round($pad + $i * $step, 1), round($h - $pad - ($v / $max) * ($h - $pad * 2 - 6), 1)];
    }
    $line = implode(' ', array_map(static fn($p) => $p[0] . ',' . $p[1], $xy));
    $area = $pad . ',' . ($h - $pad) . ' ' . $line . ' ' . ($w - $pad) . ',' . ($h - $pad);
    $svg = '<svg viewBox="0 0 ' . $w . ' ' . ($h + 16) . '" class="dash-spark" role="img" aria-label="Last 7 days">'
        . '<polygon points="' . $area . '" fill="' . $color . '" fill-opacity=".14"/>'
        . '<polyline points="' . $line . '" fill="none" stroke="' . $color . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
    foreach ($xy as $i => [$x, $y]) {
        $day = strtotime($keys[$i]);
        $svg .= '<g class="dash-hit" data-tip="' . e(date('D j M', $day) . ': ' . $label($values[$i])) . '">'
            . '<rect x="' . ($x - $step / 2) . '" y="0" width="' . $step . '" height="' . $h . '" fill="transparent"/>'
            . '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="' . $color . '" stroke="#fff" stroke-width="2" class="dash-dot"/></g>'
            . '<text x="' . $x . '" y="' . ($h + 12) . '" text-anchor="middle" class="dash-axis">' . e(date('D', $day)[0]) . '</text>';
    }
    return $svg . '</svg>';
};

$monthly = $c['monthly'] ?? [];
$revenue = $c['revenue'] ?? [];
$enrolments = $c['enrolments'] ?? [];
$byOrg = $c['byOrganisation'] ?? [];
$orgMax = max(1, ...array_map(static fn($r) => (int) $r['n'], $byOrg ?: [['n' => 1]]));

// Attachments: share of all requests in each stage, as concentric rings.
$att = $attachmentStats ?? [];
$attTotal = array_sum(array_map('intval', $att));
$attRings = [
    ['Requested', (int) ($att['pending'] ?? 0) + (int) ($att['paused'] ?? 0), $series[0]],
    ['Accepted', (int) ($att['accepted'] ?? 0), $series[1]],
    ['Completed', (int) ($att['completed'] ?? 0) + (int) ($att['recommended'] ?? 0), $series[2]],
];

// People by role: the five biggest roles, the rest folded into "Other".
$roles = $roleCounts ?? [];
usort($roles, static fn($a, $b) => (int) $b['total'] <=> (int) $a['total']);
$slices = [];
foreach ($roles as $i => $row) {
    if ((int) $row['total'] < 1) { continue; }
    if (count($slices) < 5) {
        $slices[] = [$row['name'], (int) $row['total'], $series[count($slices)]];
    } else {
        $slices[5] ??= ['Other', 0, '#8a8984'];
        $slices[5][1] += (int) $row['total'];
    }
}
$peopleTotal = array_sum(array_map(static fn($s) => $s[1], $slices));
?>

<style>
  .dash-grid{display:grid;gap:16px;grid-template-columns:repeat(12,minmax(0,1fr));}
  .dash-card{grid-column:span 12;min-width:0;padding:18px 20px;border-radius:18px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px rgba(15,23,42,.06);}
  .dash-card h3{font-size:.8rem;font-weight:800;color:#1f2937;}
  .dash-card-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;}
  .dash-chip{padding:.25rem .65rem;border-radius:999px;background:#374151;color:#fff;font-size:.66rem;font-weight:800;white-space:nowrap;}
  .dash-big{font-size:1.6rem;font-weight:900;color:#111827;line-height:1.1;}
  .dash-sub{font-size:.72rem;font-weight:700;color:#6b7280;}
  .dash-axis{font-size:9px;font-weight:700;fill:#6b7280;}
  .dash-grid-line{stroke:#eceae4;stroke-width:1;}
  .dash-spark{width:100%;height:auto;display:block;}
  .dash-hit{cursor:default;}
  .dash-hit .dash-dot{opacity:0;transition:opacity .1s;}
  .dash-hit:hover .dash-dot,.dash-hit:focus .dash-dot{opacity:1;}
  .dash-hit:hover rect.dash-bar{filter:brightness(.88);}
  .dash-bars-row{display:flex;flex-direction:column;gap:10px;}
  .dash-hbar{display:grid;grid-template-columns:minmax(0,9rem) 1fr auto;align-items:center;gap:10px;font-size:.78rem;font-weight:700;color:#374151;}
  .dash-hbar-track{height:10px;border-radius:999px;background:#f1f2ef;overflow:hidden;}
  .dash-hbar-fill{height:100%;border-radius:999px;}
  .dash-legend{display:flex;flex-direction:column;gap:8px;font-size:.78rem;font-weight:700;color:#374151;}
  .dash-legend li{display:flex;align-items:center;gap:8px;}
  .dash-swatch{width:10px;height:10px;border-radius:3px;flex-shrink:0;}
  .dash-legend .n{margin-left:auto;color:#111827;font-weight:900;}
  .dash-person{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f3f4f6;}
  .dash-person:last-child{border-bottom:0;}
  .dash-avatar{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#1f2937;color:#fff;font-size:.8rem;font-weight:900;flex-shrink:0;}
  .dash-pill{margin-left:auto;padding:.2rem .6rem;border-radius:999px;font-size:.62rem;font-weight:900;text-transform:uppercase;white-space:nowrap;}
  #dash-tip{position:fixed;z-index:80;pointer-events:none;padding:6px 9px;border-radius:8px;background:#111827;color:#fff;font-size:.72rem;font-weight:700;white-space:nowrap;opacity:0;transition:opacity .08s;}
  @media(min-width:768px){.dash-md-6{grid-column:span 6;}}
  @media(min-width:1280px){
    .dash-xl-7{grid-column:span 7;}.dash-xl-5{grid-column:span 5;}.dash-xl-4{grid-column:span 4;}
    .dash-xl-3{grid-column:span 3;}.dash-xl-8{grid-column:span 8;}.dash-xl-row2{grid-row:span 2;}
  }
</style>

<section class="dash-grid" aria-label="Platform summary">
  <!-- Students by organisation -->
  <article class="dash-card dash-xl-7">
    <div class="dash-card-head"><h3>Students by organisation</h3><span class="dash-chip">Top <?= count($byOrg) ?: 0 ?></span></div>
    <?php if (!$byOrg): ?>
      <p class="dash-sub">Students appear here once organisations approve them.</p>
    <?php else: ?>
      <div class="dash-bars-row">
        <?php foreach ($byOrg as $row): ?>
          <div class="dash-hbar dash-hit" data-tip="<?= e($row['name'] . ': ' . (int) $row['n'] . ' students') ?>">
            <span class="truncate" title="<?= e($row['name']) ?>"><?= e($row['name']) ?></span>
            <span class="dash-hbar-track"><span class="dash-hbar-fill" style="display:block;width:<?= max(3, round((int) $row['n'] / $orgMax * 100)) ?>%;background:<?= $brand ?>"></span></span>
            <span><?= $fmt((int) $row['n']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <!-- Attachments by stage -->
  <article class="dash-card dash-xl-5">
    <div class="dash-card-head"><h3>Attachments</h3><a href="<?= url('/admin/attachments') ?>" class="dash-chip">View all</a></div>
    <div class="flex flex-wrap items-center gap-6">
      <svg viewBox="0 0 140 140" width="140" height="140" role="img" aria-label="Attachment requests by stage">
        <?php foreach ($attRings as $i => [$label, $n, $color]):
          $r = 60 - $i * 14; $len = 2 * M_PI * $r; $share = $attTotal > 0 ? $n / $attTotal : 0;
        ?>
          <circle cx="70" cy="70" r="<?= $r ?>" fill="none" stroke="#f1f2ef" stroke-width="8"/>
          <g class="dash-hit" data-tip="<?= e($label . ': ' . $n . ($attTotal ? ' (' . round($share * 100) . '%)' : '')) ?>">
            <circle cx="70" cy="70" r="<?= $r ?>" fill="none" stroke="<?= $color ?>" stroke-width="8" stroke-linecap="round"
                    stroke-dasharray="<?= round($len * $share, 1) ?> <?= round($len, 1) ?>" transform="rotate(-90 70 70)" <?= $share > 0 ? '' : 'stroke-opacity="0"' ?>/>
          </g>
        <?php endforeach; ?>
        <text x="70" y="68" text-anchor="middle" style="font-size:20px;font-weight:900;fill:#111827"><?= $fmt($attTotal) ?></text>
        <text x="70" y="84" text-anchor="middle" class="dash-axis">requests</text>
      </svg>
      <ul class="dash-legend min-w-0 flex-1" style="min-width:12rem">
        <?php foreach ($attRings as [$label, $n, $color]): ?>
          <li>
            <span class="dash-swatch" style="background:<?= $color ?>"></span><?= e($label) ?><span class="n"><?= $fmt($n) ?></span>
          </li>
          <li style="margin-top:-4px"><span class="dash-hbar-track" style="flex:1"><span class="dash-hbar-fill" style="display:block;width:<?= $attTotal ? round($n / $attTotal * 100) : 0 ?>%;background:<?= $color ?>"></span></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </article>

  <!-- New students per month -->
  <article class="dash-card dash-xl-4 dash-xl-row2">
    <div class="dash-card-head"><h3>New students</h3><span class="dash-chip">7 months</span></div>
    <?php
      $mv = array_values($monthly); $mk = array_keys($monthly);
      $mMax = max(4, max($mv ?: [0]));
      // A clean axis: the top is 4 steps of 1, 2, 2.5 or 5 × 10ⁿ.
      $raw = $mMax / 4; $mag = 10 ** floor(log10($raw));
      foreach ([1, 2, 2.5, 5, 10] as $m) { if ($m * $mag >= $raw) { $stepSize = $m * $mag; break; } }
      $mTop = (int) round($stepSize * 4);
      $W = 300; $H = 240; $L = 28; $B = 22; $bw = ($W - $L) / max(1, count($mv));
      $bestIndex = $mv ? array_search(max($mv), $mv, true) : -1;
    ?>
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full" style="height:auto;max-height:300px" role="img" aria-label="New students per month">
      <?php for ($g = 0; $g <= 4; $g++): $gy = $H - $B - ($H - $B - 10) * $g / 4; ?>
        <line x1="<?= $L ?>" x2="<?= $W ?>" y1="<?= $gy ?>" y2="<?= $gy ?>" class="dash-grid-line"/>
        <text x="<?= $L - 6 ?>" y="<?= $gy + 3 ?>" text-anchor="end" class="dash-axis"><?= (int) ($mTop * $g / 4) ?></text>
      <?php endfor; ?>
      <?php foreach ($mv as $i => $v):
        $bh = ($H - $B - 10) * $v / max(1, $mTop); $x = $L + $i * $bw + $bw * 0.3; $w = $bw * 0.4; $y = $H - $B - $bh;
      ?>
        <g class="dash-hit" data-tip="<?= e(date('F Y', strtotime($mk[$i] . '-01')) . ': ' . $v . ' new students') ?>">
          <rect x="<?= $L + $i * $bw ?>" y="0" width="<?= $bw ?>" height="<?= $H - $B ?>" fill="transparent"/>
          <?php if ($v > 0): ?><rect class="dash-bar" x="<?= round($x, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($w, 1) ?>" height="<?= round($bh, 1) ?>" rx="4" fill="<?= $brand ?>"/><?php endif; ?>
          <?php if ($i === $bestIndex && $v > 0): ?><text x="<?= round($x + $w / 2, 1) ?>" y="<?= round($y - 5, 1) ?>" text-anchor="middle" style="font-size:10px;font-weight:900;fill:#111827"><?= $v ?></text><?php endif; ?>
        </g>
        <text x="<?= round($x + $w / 2, 1) ?>" y="<?= $H - 6 ?>" text-anchor="middle" class="dash-axis"><?= e(date('M', strtotime($mk[$i] . '-01'))) ?></text>
      <?php endforeach; ?>
    </svg>
    <p class="dash-sub mt-2"><?= $fmt(array_sum($mv)) ?> students joined in the last 7 months.</p>
  </article>

  <!-- Course payments this week -->
  <article class="dash-card dash-md-6 dash-xl-4">
    <div class="dash-card-head"><h3>Course payments · last 7 days</h3></div>
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div class="shrink-0">
        <p class="dash-big"><?= e($ksh(array_sum($revenue))) ?></p>
        <p class="dash-sub"><?= e($ksh((float) ($c['revenueTotal'] ?? 0))) ?> all time</p>
      </div>
      <div class="min-w-0 flex-1" style="min-width:170px"><?= $spark($revenue, $brand, static fn($v) => 'Ksh ' . number_format($v)) ?></div>
    </div>
  </article>

  <!-- Enrolments this week -->
  <article class="dash-card dash-md-6 dash-xl-4">
    <div class="dash-card-head"><h3>Enrolments · last 7 days</h3></div>
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div class="shrink-0">
        <p class="dash-big"><?= $fmt(array_sum($enrolments)) ?></p>
        <p class="dash-sub"><?= $fmt((int) ($c['courses']['published'] ?? 0)) ?> of <?= $fmt((int) ($c['courses']['total'] ?? 0)) ?> courses published</p>
      </div>
      <div class="min-w-0 flex-1" style="min-width:170px"><?= $spark($enrolments, $series[5], static fn($v) => (int) $v . ' enrolments') ?></div>
    </div>
  </article>

  <!-- People by role -->
  <article class="dash-card dash-md-6 dash-xl-5">
    <div class="dash-card-head"><h3>People by role</h3><a href="<?= url('/admin/users') ?>" class="dash-chip">All users</a></div>
    <div class="flex flex-wrap items-center gap-6">
      <svg viewBox="0 0 160 160" width="150" height="150" role="img" aria-label="People by role">
        <?php
          $r = 58; $len = 2 * M_PI * $r; $offset = 0;
          if (!$slices): ?><circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke="#f1f2ef" stroke-width="24"/><?php endif;
          foreach ($slices as [$label, $n, $color]):
            $seg = $peopleTotal ? $len * $n / $peopleTotal : 0;
            $gap = count($slices) > 1 ? min(2, $seg / 2) : 0; // 2px surface gap between slices
        ?>
          <g class="dash-hit" data-tip="<?= e($label . ': ' . $n . ' (' . round($n / max(1, $peopleTotal) * 100) . '%)') ?>">
            <circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke="<?= $color ?>" stroke-width="24"
                    stroke-dasharray="<?= round(max(0, $seg - $gap), 2) ?> <?= round($len, 2) ?>" stroke-dashoffset="<?= round(-$offset, 2) ?>" transform="rotate(-90 80 80)"/>
          </g>
        <?php $offset += $seg; endforeach; ?>
        <text x="80" y="80" text-anchor="middle" style="font-size:24px;font-weight:900;fill:#111827"><?= $fmt($peopleTotal) ?></text>
        <text x="80" y="96" text-anchor="middle" class="dash-axis">people</text>
      </svg>
      <ul class="dash-legend min-w-0 flex-1" style="min-width:12rem">
        <?php foreach ($slices as [$label, $n, $color]): ?>
          <li><span class="dash-swatch" style="background:<?= $color ?>"></span><span class="truncate"><?= e($label) ?></span><span class="n"><?= $fmt($n) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$slices): ?><li class="dash-sub">No users yet.</li><?php endif; ?>
      </ul>
    </div>
  </article>

  <!-- Recent users -->
  <article class="dash-card dash-md-6 dash-xl-3">
    <div class="dash-card-head"><h3>Newest people</h3><a href="<?= url('/admin/users') ?>" class="dash-chip">View all</a></div>
    <?php if (!$recentUsers): ?>
      <p class="dash-sub">No users yet.</p>
    <?php endif; ?>
    <?php foreach (array_slice($recentUsers, 0, 5) as $person):
      $status = (string) ($person['account_status'] ?? (!empty($person['is_active']) ? 'active' : 'blocked'));
    ?>
      <a href="<?= url('/admin/users/' . (int) $person['id']) ?>" class="dash-person">
        <span class="dash-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(userDisplayName($person), 0, 1))) ?></span>
        <span class="min-w-0">
          <span class="block truncate text-xs font-black text-gray-900"><?= e(userDisplayName($person)) ?></span>
          <span class="block truncate text-[10px] font-semibold text-gray-500"><?= e($person['role_name'] ?? '') ?></span>
        </span>
        <span class="dash-pill" style="<?= $status === 'active' ? 'background:#ecf7f0;color:#006b3f' : 'background:#fef2f2;color:#b91c1c' ?>"><?= e(ucfirst($status)) ?></span>
      </a>
    <?php endforeach; ?>
  </article>
</section>

<div id="dash-tip" role="tooltip"></div>
<script>
(function () {
  var tip = document.getElementById('dash-tip');
  document.querySelectorAll('.dash-hit[data-tip]').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      tip.textContent = el.getAttribute('data-tip');
      tip.style.opacity = 1;
      var x = Math.min(e.clientX + 12, window.innerWidth - tip.offsetWidth - 8);
      tip.style.left = x + 'px';
      tip.style.top = (e.clientY - tip.offsetHeight - 10) + 'px';
    });
    el.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
  });
})();
</script>
