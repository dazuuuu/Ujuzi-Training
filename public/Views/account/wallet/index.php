<?php
/** Requires $mode ('student'|'earner'|'none') and the data for that mode. */
require __DIR__ . '/../layout-header.php';
use App\Services\WalletService;
$fmt = static fn(float $n): string => number_format($n, 2);
$coinsFmt = static fn(float $ksh): string => rtrim(rtrim(number_format(WalletService::coins($ksh), 2), '0'), '.') ?: '0';
$who = static fn(array $r, string $p): string => trim(($r[$p . '_first'] ?? '') . ' ' . ($r[$p . '_last'] ?? '')) ?: (string) ($r[$p . '_email'] ?? '—');
?>
<div class="space-y-6">
<?php if ($mode === 'student'): ?>
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">My wallet</h1>
    <p class="text-xs font-medium text-neutral-500">Ksh 100 = <?= e(rtrim(rtrim(number_format(WalletService::coinsPer100(), 2), '0'), '.')) ?> coins · pay at least <?= \App\Services\WalletService::minPaymentPercent() ?>% of a course to enrol.</p>
  </section>

  <div class="wallet-strip">
    <div class="wallet-balance">
      <span class="wallet-label">Balance</span>
      <strong>Ksh <?= $fmt($balanceKsh) ?></strong>
      <span class="wallet-coins">🪙 <?= e($coinsFmt($balanceKsh)) ?> coins</span>
    </div>
    <div class="wallet-mini"><span class="wallet-label">Deposited</span><span>Ksh <?= $fmt($depositedKsh) ?></span></div>
    <div class="wallet-mini"><span class="wallet-label">Spent on courses</span><span>Ksh <?= $fmt($spentKsh) ?></span></div>
  </div>

  <?php $depositReturn = '/account/wallet'; require __DIR__ . '/../partials/deposit-form.php'; ?>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">My course payments</h2>
    <?php if (!$courses): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No paid courses yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Course</th><th>Fee</th><th>Paid</th><th>Balance</th><th></th></tr></thead><tbody>
      <?php foreach ($courses as $c): $left = max(0, (float) $c['fee'] - (float) $c['paid']); ?>
        <tr><td class="font-black"><?= e($c['title']) ?></td><td>Ksh <?= $fmt((float) $c['fee']) ?></td><td>Ksh <?= $fmt((float) $c['paid']) ?></td>
          <td><?= $left > 0 ? 'Ksh ' . $fmt($left) : '<span class="text-[11px] font-black uppercase" style="color:var(--ke-green)">Paid</span>' ?></td>
          <td><?php if ($left > 0): ?><a class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;" href="<?= url('/account/courses/' . (int) $c['id'] . '/checkout') ?>">Pay balance</a><?php endif; ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>

  <section class="space-y-3">
    <h2 class="font-serif-heading text-lg font-bold">Where my money went</h2>
    <?php if (!$transactions): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No transactions yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Date</th><th>Type</th><th>Details</th><th>Amount</th><th>Coins</th><th>Reference</th></tr></thead><tbody>
      <?php foreach ($transactions as $t): $in = $t['type'] === 'deposit'; ?>
        <tr><td><?= e(date('j M Y H:i', strtotime((string) $t['created_at']))) ?></td>
          <td><span class="text-[11px] font-black uppercase" style="color:<?= $in ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $in ? 'Deposit' : 'Course payment' ?></span></td>
          <td><?= e($in ? ($t['note'] ?? '') : ($t['course_title'] ?? $t['note'] ?? '')) ?></td>
          <td class="font-black" style="color:<?= $in ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $in ? '+' : '−' ?>Ksh <?= $fmt((float) $t['amount_ksh']) ?></td>
          <td><?= $in ? '+' : '−' ?><?= e($coinsFmt((float) $t['amount_ksh'])) ?></td><td><?= e($t['reference'] ?? '') ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </section>

<?php elseif ($mode === 'earner'):
  $collected = array_sum(array_map(static fn($c) => (float) $c['collected'], $courses));
  $expected = array_sum(array_map(static fn($c) => (float) $c['fee'] * (int) $c['students'], $courses));
  $isOrg = $scope !== 'tutor';
  $dateQs = http_build_query(array_filter(['from' => $from ?? '', 'to' => $to ?? '']));
?>
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $isOrg ? 'Organisation finances' : 'My earnings' ?></p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $isOrg ? 'Finances' : 'Earnings' ?></h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Every time a student pays for <?= $scope === 'branch' ? 'a course, students of your branch' : ($isOrg ? 'one of your organisation\'s courses' : 'one of your courses') ?>, it is credited here.</p>
  </section>
  <form method="get" action="<?= url('/account/wallet') ?>" class="flex flex-wrap items-end gap-2 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm print:hidden">
    <label class="text-xs font-bold uppercase text-neutral-600">From<input type="date" name="from" value="<?= e($from ?? '') ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
    <label class="text-xs font-bold uppercase text-neutral-600">To<input type="date" name="to" value="<?= e($to ?? '') ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
    <button type="submit" class="btn-secondary">Show</button>
    <?php if ($dateQs !== ''): ?><a href="<?= url('/account/wallet') ?>" class="self-center text-xs font-bold underline">All dates</a><?php endif; ?>
    <span class="ml-auto flex flex-wrap gap-2">
      <a href="<?= e(url('/account/wallet/export/xlsx') . ($dateQs !== '' ? '?' . $dateQs : '')) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Excel</a>
      <a href="<?= e(url('/account/wallet/export/pdf') . ($dateQs !== '' ? '?' . $dateQs : '')) ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2"><?= icon('file', 'h-4 w-4') ?> View PDF</a>
      <button type="button" class="btn-secondary" onclick="window.print()">Print</button>
    </span>
  </form>
  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total earned</p><p class="mt-2 text-3xl font-black" style="color:var(--ke-green)">Ksh <?= $fmt($collected) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Expected from enrolled students</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($expected) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Still to be paid</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt(max(0, $expected - $collected)) ?></p></div>
  </div>

  <section class="space-y-3"><h2 class="font-serif-heading text-lg font-bold">By course</h2>
    <?php if (!$courses): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No courses yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Course</th><?php if ($isOrg): ?><th>Tutor</th><?php endif; ?><th>Fee</th><th>Students</th><th>Collected</th><th>Balance due</th></tr></thead><tbody>
      <?php foreach ($courses as $c): $exp = (float) $c['fee'] * (int) $c['students']; ?>
        <tr><td class="font-black"><?= e($c['title']) ?></td><?php if ($isOrg): ?><td><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: (string) ($c['email'] ?? '')) ?></td><?php endif; ?>
          <td>Ksh <?= $fmt((float) $c['fee']) ?></td><td><?= (int) $c['students'] ?></td><td class="font-black">Ksh <?= $fmt((float) $c['collected']) ?></td><td>Ksh <?= $fmt(max(0, $exp - (float) $c['collected'])) ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?></section>

  <section class="space-y-3"><h2 class="font-serif-heading text-lg font-bold">Payments received</h2>
    <?php if (!$lines): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No payments received yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Date</th><th>Student</th><th>Course</th><?php if ($isOrg): ?><th>Tutor</th><?php endif; ?><th>Amount</th><th>Reference</th></tr></thead><tbody>
      <?php foreach ($lines as $l): ?>
        <tr><td><?= e(date('j M Y H:i', strtotime((string) $l['created_at']))) ?></td><td class="font-black"><?= e($who($l, 'payer')) ?></td><td><?= e($l['course_title'] ?? '') ?></td>
          <?php if ($isOrg): ?><td><?= e($who($l, 'tutor')) ?></td><?php endif; ?><td class="font-black" style="color:var(--ke-green)">+Ksh <?= $fmt((float) $l['amount_ksh']) ?></td><td><?= e($l['reference'] ?? '') ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php else: ?>
  <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">There is no wallet for this account type.</p>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
