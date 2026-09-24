<?php
/** Requires $summary, $organisations, $transactions, $coinsPer100, $paymentMode. */
require __DIR__ . '/../layout-header.php';
$fmt = static fn(float $n): string => number_format($n, 2);
$totalCollected = array_sum(array_map(static fn($o) => (float) $o['collected'], $organisations));
?>
<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Finance</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Finance overview</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Payment mode: <strong><?= $paymentMode === 'live' ? 'Live' : 'Simulation (no real money)' ?></strong></p>
  </section>

  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total earned by the system (course payments)</p><p class="mt-2 text-3xl font-black" style="color:var(--ke-green)">Ksh <?= $fmt($summary['earned']) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total deposited by students</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($summary['deposited']) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Unspent in student wallets</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($summary['unspent']) ?></p></div>
  </div>

  <section class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)">
    <form method="post" action="<?= url('/admin/finance/rate') ?>" class="flex flex-wrap items-end gap-3">
      <?= csrfField() ?>
      <label class="text-xs font-bold uppercase text-neutral-600">Coins per Ksh 100
        <input type="number" name="coins_per_100_ksh" step="0.01" min="0.01" value="<?= e((string) $coinsPer100) ?>" class="mt-1 block w-40 rounded-lg border border-neutral-300 p-2 text-sm" /></label>
      <button type="submit" class="btn-primary">Save rate</button>
    </form>
  </section>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Organisations providing courses — click one to see its finances</h2>
    <?php if (!$organisations): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No course payments yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Organisation</th><th>Courses</th><th>Collected</th><th>Expected</th><th>Balance due</th><th></th></tr></thead><tbody>
      <?php foreach ($organisations as $o): ?>
        <tr><td class="font-black"><a href="<?= url('/admin/finance/organisations/' . (int) $o['id']) ?>" style="color:var(--ke-green)"><?= e($o['name']) ?></a></td><td><?= (int) $o['courses'] ?></td><td class="font-black" style="color:var(--ke-green)">Ksh <?= $fmt((float) $o['collected']) ?></td><td>Ksh <?= $fmt((float) $o['expected']) ?></td><td>Ksh <?= $fmt(max(0, (float) $o['expected'] - (float) $o['collected'])) ?></td><td><a class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;" href="<?= url('/admin/finance/organisations/' . (int) $o['id']) ?>">View finances</a></td></tr>
      <?php endforeach; ?>
        <tr><td class="font-black">Total</td><td></td><td class="font-black">Ksh <?= $fmt($totalCollected) ?></td><td></td><td></td><td></td></tr>
      </tbody></table></div><?php endif; ?>
  </section>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Recent transactions</h2>
    <?php if (!$transactions): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No transactions yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Date</th><th>Student</th><th>Type</th><th>Course</th><th>Organisation</th><th>Amount</th><th>Reference</th></tr></thead><tbody>
      <?php foreach ($transactions as $t): $in = $t['type'] === 'deposit'; ?>
        <tr><td><?= e(date('j M Y H:i', strtotime((string) $t['created_at']))) ?></td><td class="font-black"><?= e(userDisplayName($t)) ?></td><td><?= $in ? 'Deposit' : 'Course payment' ?></td><td><?= e($t['course_title'] ?? '—') ?></td><td><?= e($t['organisation_name'] ?? '—') ?></td><td class="font-black">Ksh <?= $fmt((float) $t['amount_ksh']) ?></td><td><?= e($t['reference'] ?? '') ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
