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
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">My wallet</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Wallet &amp; coins</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Ksh 100 = <?= e(rtrim(rtrim(number_format(WalletService::coinsPer100(), 2), '0'), '.')) ?> coins. Deposit money to earn coins, then pay for courses bit by bit (at least 20% to enrol).</p>
  </section>

  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Coins available</p><p class="mt-2 text-3xl font-black" style="color:var(--ke-green)">🪙 <?= e($coinsFmt($balanceKsh)) ?></p><p class="text-xs font-semibold text-neutral-500">= Ksh <?= $fmt($balanceKsh) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total deposited</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($depositedKsh) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total spent on courses</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($spentKsh) ?></p></div>
  </div>

  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-3" style="border-color:var(--ke-line)">
    <h2 class="font-serif-heading text-lg font-bold">Deposit (M-Pesa)</h2>
    <?php if (\App\Services\PaymentGateway::mode() === 'simulation'): ?>
      <p class="text-xs font-bold" style="color:var(--ke-red)">Simulation mode: no real money is charged. Deposits are approved instantly.</p>
    <?php endif; ?>
    <form method="post" action="<?= url('/account/wallet/deposit') ?>" class="grid gap-3 sm:grid-cols-3 sm:items-end">
      <?= csrfField() ?>
      <label class="block text-xs font-bold uppercase text-neutral-600">Amount (Ksh)<input type="number" name="amount_ksh" min="10" step="1" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm" /></label>
      <label class="block text-xs font-bold uppercase text-neutral-600">M-Pesa phone<input type="text" name="phone" placeholder="0712345678" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm" /></label>
      <button type="submit" class="btn-primary">Deposit</button>
    </form>
  </section>

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
  $isOrg = $scope === 'organisation';
?>
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $isOrg ? 'Organisation finances' : 'My earnings' ?></p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $isOrg ? 'Finances' : 'Earnings' ?></h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Every time a student pays for <?= $isOrg ? 'one of your organisation\'s courses' : 'one of your courses' ?>, it is credited here.</p>
  </section>
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
