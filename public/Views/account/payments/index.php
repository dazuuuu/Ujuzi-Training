<?php
/** Requires $receipts. */
require __DIR__ . '/../layout-header.php';
?>
<div class="space-y-4">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Payment history</h1>
    <p class="text-xs font-medium text-neutral-500">A receipt for every deposit and course payment. Each one is also emailed to you.</p>
  </section>
  <?php if (!$receipts): ?>
    <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No payments yet.</p>
  <?php else: ?>
  <div class="excel-sheet-wrap">
    <table class="excel-sheet">
      <thead><tr><th>Receipt</th><th>Date</th><th>For</th><th>Type</th><th style="text-align:right">Amount (Ksh)</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($receipts as $r): ?>
        <tr>
          <td><?= e($r['receipt_no']) ?></td>
          <td><?= e(date('j M Y, H:i', strtotime((string) $r['created_at']))) ?></td>
          <td><?= e($r['description']) ?></td>
          <td><?= $r['kind'] === 'deposit' ? 'Deposit' : 'Course payment' ?></td>
          <td style="text-align:right"><?= number_format((float) $r['amount_ksh'], 2) ?></td>
          <td><a class="font-bold" style="color:var(--ke-green)" href="<?= url('/account/payments/' . (int) $r['id']) ?>" target="_blank">View / download</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
