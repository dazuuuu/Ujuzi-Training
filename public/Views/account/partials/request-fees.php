<?php
/**
 * "Paid: 2,000 / 10,000" for the course an attachment request is about — red
 * while anything is owed. Requires $requestFees (WalletService::requestFees).
 */
$rf = $requestFees ?? null;
?>
<?php if ($rf && $rf['fee'] > 0): $owes = $rf['balance'] > 0; ?>
  <p class="text-xs font-black" style="color:<?= $owes ? 'var(--ke-red)' : 'var(--ke-green)' ?>" title="<?= e(implode(', ', $rf['titles'])) ?>">
    Paid: <?= number_format($rf['paid']) ?> / <?= number_format($rf['fee']) ?>
  </p>
  <p class="text-[10px] font-semibold text-neutral-500"><?= e(implode(', ', $rf['titles'])) ?><?= $owes ? ' · owes Ksh ' . number_format($rf['balance']) : ' · fully paid' ?></p>
<?php elseif ($rf && $rf['titles']): ?>
  <p class="text-xs font-black" style="color:var(--ke-green)">Free course</p>
<?php endif; ?>
