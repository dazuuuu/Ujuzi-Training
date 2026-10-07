<?php
/**
 * A student's fees and progress, course by course, for attachment reviewers.
 * Requires $fees: one entry from WalletService::feeSummaries(), or null.
 */
$fees = $fees ?? null;
$minPct = \App\Services\WalletService::minPaymentPercent();
?>
<?php if (!$fees || $fees['courses'] === 0): ?>
  <span class="text-xs font-semibold" style="color:var(--ke-muted)">No courses</span>
<?php else: ?>
  <div class="space-y-2" style="min-width:14rem">
    <?php // Each course on its own line: money paid for one course never counts towards another. ?>
    <ul class="space-y-1.5">
      <?php foreach ($fees['items'] as $item): ?>
        <li class="flex items-center gap-2">
          <?php $pct = (int) $item['progress']; $ringSize = 34; $ringLabel = $item['title'] . ' progress'; require __DIR__ . '/progress-ring.php'; ?>
          <span class="min-w-0">
            <span class="block truncate text-[11px] font-bold text-neutral-800" title="<?= e($item['title']) ?>"><?= e($item['title']) ?></span>
            <span class="block text-[10px] font-semibold text-neutral-500">
              <?= (int) $item['modules_passed'] ?>/<?= (int) $item['modules_total'] ?> modules<?= $item['final_passed'] ? ' · exam passed' : '' ?>
              · paid Ksh <?= number_format($item['paid'], 2) ?> of <?= number_format($item['fee'], 2) ?>
              · <?= $item['balance'] > 0 ? '<span style="color:var(--ke-red)">owes Ksh ' . number_format($item['balance'], 2) . '</span>' : 'cleared' ?>
              <?php if (!empty($item['below_minimum'])): ?> · <span style="color:#b45309">below <?= $minPct ?>%</span><?php endif; ?>
            </span>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
