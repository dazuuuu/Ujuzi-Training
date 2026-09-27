<?php
/**
 * A student's course fees and progress, for attachment reviewers.
 * Requires $fees: one entry from WalletService::feeSummaries(), or null.
 */
$fees = $fees ?? null;
$minPct = \App\Services\WalletService::minPaymentPercent();
?>
<?php if (!$fees || $fees['courses'] === 0): ?>
  <span class="text-xs font-semibold" style="color:var(--ke-muted)">No courses</span>
<?php else:
  $paidPercent = (int) floor($fees['paid_ratio'] * 100);
?>
  <div class="space-y-2" style="min-width:14rem">
    <div class="text-xs font-semibold text-neutral-700">
      Paid <strong>Ksh <?= number_format($fees['paid'], 2) ?></strong> of <?= number_format($fees['fee'], 2) ?>
      <span class="text-neutral-500">(<?= $paidPercent ?>%)</span>
    </div>
    <div class="text-[11px] font-black uppercase" style="color:<?= $fees['is_settled'] ? 'var(--ke-green)' : 'var(--ke-red)' ?>">
      <?= $fees['is_settled'] ? 'Cleared — balance Ksh 0.00' : 'Balance Ksh ' . number_format($fees['balance'], 2) ?>
    </div>

    <?php if ($fees['below_minimum']): ?>
      <p class="rounded border px-2 py-1 text-[11px] font-bold" role="note" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
        <?= icon('alert', 'inline h-3.5 w-3.5 align-[-2px]') ?> Paid only <?= $paidPercent ?>% of their fees — below the <?= $minPct ?>% minimum. You can still accept them, but until they pay <?= $minPct ?>% they can't send attachment requests.
      </p>
    <?php endif; ?>

    <ul class="space-y-1.5">
      <?php foreach ($fees['items'] as $item): ?>
        <li class="flex items-center gap-2">
          <?php $pct = (int) $item['progress']; $ringSize = 34; $ringLabel = $item['title'] . ' progress'; require __DIR__ . '/progress-ring.php'; ?>
          <span class="min-w-0">
            <span class="block truncate text-[11px] font-bold text-neutral-800" title="<?= e($item['title']) ?>"><?= e($item['title']) ?></span>
            <span class="block text-[10px] font-semibold text-neutral-500">
              <?= (int) $item['modules_passed'] ?>/<?= (int) $item['modules_total'] ?> modules<?= $item['final_passed'] ? ' · exam passed' : '' ?>
              · <?= $item['balance'] > 0 ? 'owes Ksh ' . number_format($item['balance'], 2) : 'paid' ?>
            </span>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
