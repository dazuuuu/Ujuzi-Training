<?php
/** Requires $organisation, $courses, $lines. */
require __DIR__ . '/../layout-header.php';
$fmt = static fn(float $n): string => number_format($n, 2);
$who = static fn(array $r, string $p): string => trim(($r[$p . '_first'] ?? '') . ' ' . ($r[$p . '_last'] ?? '')) ?: (string) ($r[$p . '_email'] ?? '—');
$collected = array_sum(array_map(static fn($c) => (float) $c['collected'], $courses));
$expected = array_sum(array_map(static fn($c) => (float) $c['fee'] * (int) $c['students'], $courses));
$tutors = [];
foreach ($courses as $c) {
    $key = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: (string) ($c['email'] ?? '—');
    $tutors[$key] = ($tutors[$key] ?? 0) + (float) $c['collected'];
}
arsort($tutors);
?>
<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Finance tracking</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($organisation['name']) ?></h1>
    </div>
    <a href="<?= url('/admin/finance') ?>" class="btn-secondary">Back to finance overview</a>
  </section>

  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Total earned</p><p class="mt-2 text-3xl font-black" style="color:var(--ke-green)">Ksh <?= $fmt($collected) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Expected from enrolled students</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt($expected) ?></p></div>
    <div class="rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Still to be paid</p><p class="mt-2 text-2xl font-black">Ksh <?= $fmt(max(0, $expected - $collected)) ?></p></div>
  </div>

  <section class="space-y-3"><h2 class="font-serif-heading text-lg font-bold">Earnings by tutor</h2>
    <?php if (!$tutors): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No courses yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Tutor</th><th>Earned</th></tr></thead><tbody>
      <?php foreach ($tutors as $name => $amount): ?><tr><td class="font-black"><?= e($name) ?></td><td>Ksh <?= $fmt($amount) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>

  <section class="space-y-3"><h2 class="font-serif-heading text-lg font-bold">By course</h2>
    <?php if ($courses): ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Course</th><th>Tutor</th><th>Fee</th><th>Students</th><th>Collected</th><th>Balance due</th></tr></thead><tbody>
      <?php foreach ($courses as $c): $exp = (float) $c['fee'] * (int) $c['students']; ?>
        <tr><td class="font-black"><?= e($c['title']) ?></td><td><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: (string) ($c['email'] ?? '')) ?></td><td>Ksh <?= $fmt((float) $c['fee']) ?></td><td><?= (int) $c['students'] ?></td><td class="font-black">Ksh <?= $fmt((float) $c['collected']) ?></td><td>Ksh <?= $fmt(max(0, $exp - (float) $c['collected'])) ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?></section>

  <section class="space-y-3"><h2 class="font-serif-heading text-lg font-bold">Payments received</h2>
    <?php if (!$lines): ?><p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No payments received yet.</p><?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm"><table class="excel-table admin-data-table">
      <thead><tr><th>Date</th><th>Student</th><th>Course</th><th>Tutor</th><th>Amount</th><th>Reference</th></tr></thead><tbody>
      <?php foreach ($lines as $l): ?>
        <tr><td><?= e(date('j M Y H:i', strtotime((string) $l['created_at']))) ?></td><td class="font-black"><?= e($who($l, 'payer')) ?></td><td><?= e($l['course_title'] ?? '') ?></td><td><?= e($who($l, 'tutor')) ?></td><td class="font-black" style="color:var(--ke-green)">+Ksh <?= $fmt((float) $l['amount_ksh']) ?></td><td><?= e($l['reference'] ?? '') ?></td></tr>
      <?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
