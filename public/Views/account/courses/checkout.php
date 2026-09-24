<?php
/** Requires $course, $paidKsh, $balanceKsh, $minimumKsh. */
use App\Services\WalletService;
$fee = max(0, (float) ($course['enrollment_fee_ksh'] ?? 0));
$left = max(0, $fee - $paidKsh);
$coins = static fn(float $ksh): string => rtrim(rtrim(number_format(WalletService::coins($ksh), 2), '0'), '.') ?: '0';
$canPay = $balanceKsh >= $minimumKsh && $minimumKsh > 0;
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-3xl space-y-6">
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $paidKsh > 0 ? 'Pay balance' : 'Enrol &amp; pay' ?></p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($course['title'] ?? 'Course') ?></h1>
      <p class="mt-1 text-sm font-semibold" style="color:var(--ke-muted)"><?= e($course['organisation_name'] ?? '') ?> · Tutor: <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? '')) ?></p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Course fee</p><p class="mt-2 text-xl font-black">Ksh <?= number_format($fee, 2) ?></p><p class="text-xs font-semibold text-neutral-500">🪙 <?= e($coins($fee)) ?></p></div>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Paid so far</p><p class="mt-2 text-xl font-black">Ksh <?= number_format($paidKsh, 2) ?></p><p class="text-xs font-semibold text-neutral-500">Balance Ksh <?= number_format($left, 2) ?></p></div>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)"><p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">My wallet</p><p class="mt-2 text-xl font-black" style="color:var(--ke-green)">🪙 <?= e($coins($balanceKsh)) ?></p><p class="text-xs font-semibold text-neutral-500">= Ksh <?= number_format($balanceKsh, 2) ?></p></div>
    </div>

    <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="space-y-3">
      <?= csrfField() ?>
      <label class="block text-xs font-bold uppercase text-neutral-600">Amount to pay now (Ksh)
        <input type="number" name="amount_ksh" min="<?= e((string) $minimumKsh) ?>" max="<?= e((string) $left) ?>" step="1" value="<?= e((string) $minimumKsh) ?>" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm" />
      </label>
      <p class="text-xs font-semibold" style="color:var(--ke-muted)"><?= $paidKsh > 0 ? 'Pay any amount up to the balance.' : 'Pay at least 20% (Ksh ' . number_format($minimumKsh, 2) . ') to enrol. You can pay the rest bit by bit.' ?></p>
      <?php if (!$canPay): ?>
        <p class="text-sm font-bold" style="color:var(--ke-red)">Your wallet is too low for the minimum payment. Deposit first.</p>
      <?php endif; ?>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="btn-primary" <?= $canPay ? '' : 'disabled' ?>>Pay from wallet</button>
        <a href="<?= url('/account/wallet') ?>" class="btn-secondary">Deposit / view wallet</a>
        <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary">Back to course</a>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
