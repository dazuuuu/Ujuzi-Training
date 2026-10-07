<?php
/**
 * Enrolling on a paid course. Requires $course, $paidKsh, $balanceKsh,
 * $depositKsh (the minimum the wallet must hold to enrol), $moduleCount,
 * $plan (ModuleAccess::plan) and $isEnrolled.
 */
use App\Services\ModuleAccess;
use App\Services\WalletService;

$fee = max(0, (float) ($course['enrollment_fee_ksh'] ?? 0));
$left = max(0, $fee - $paidKsh);
$minPct = WalletService::minPaymentPercent();
$canEnrol = $isEnrolled || $balanceKsh + 0.001 >= $depositKsh;
$perModule = $moduleCount > 0 ? $plan['standard_price'] : $fee;
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-3xl space-y-6">
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-5" style="border-color:var(--ke-line)">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= $isEnrolled ? 'Payment plan' : 'Enrol' ?></p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($course['title'] ?? 'Course') ?></h1>
      <p class="mt-1 text-sm font-semibold" style="color:var(--ke-muted)"><?= e($course['organisation_name'] ?? '') ?> · Tutor: <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? '')) ?></p>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Course fee</p>
        <p class="mt-1 text-xl font-black">Ksh <?= number_format($fee, 2) ?></p>
        <p class="text-xs font-semibold text-neutral-500"><?= ModuleAccess::coins($fee) ?> coins</p>
      </div>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)"><?= $moduleCount > 0 ? 'Per module' : 'Paid at once' ?></p>
        <p class="mt-1 text-xl font-black">Ksh <?= number_format($perModule, 2) ?></p>
        <p class="text-xs font-semibold text-neutral-500"><?= $moduleCount > 0 ? $moduleCount . ' module' . ($moduleCount === 1 ? '' : 's') . ' · ' . ModuleAccess::coins($perModule) . ' coins each' : 'This course has no modules' ?></p>
      </div>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">My wallet</p>
        <p class="mt-1 text-xl font-black" style="color:var(--ke-green)">Ksh <?= number_format($balanceKsh, 2) ?></p>
        <p class="text-xs font-semibold text-neutral-500"><?= ModuleAccess::coins($balanceKsh) ?> coins</p>
      </div>
    </div>

    <div class="rounded-lg p-4 text-sm font-medium text-neutral-700" style="background:#f4faf6">
      <?php if ($moduleCount > 0): ?>
        You pay for this course <strong>one module at a time</strong>: when you open a module, its coins come out of your wallet, and it stays open for <?= ModuleAccess::ACCESS_DAYS ?> days.
      <?php else: ?>
        This course has no modules, so its full fee is paid from your wallet when you enrol.
      <?php endif; ?>
      <?php if (!$isEnrolled): ?>
        To enrol, your wallet needs at least <strong><?= ModuleAccess::coins($depositKsh) ?> coins (Ksh <?= number_format($depositKsh, 2) ?>)</strong> — the <?= $minPct ?>% minimum deposit.
      <?php endif; ?>
    </div>

    <?php if (!$canEnrol): ?>
      <div class="flex items-start gap-3 rounded-lg border p-4 text-sm font-semibold" role="alert" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
        <?= icon('alert', 'h-5 w-5 shrink-0') ?>
        <p>Your wallet has <?= ModuleAccess::coins($balanceKsh) ?> coins. Deposit at least <?= ModuleAccess::coins($depositKsh - $balanceKsh) ?> more coins (Ksh <?= number_format($depositKsh - $balanceKsh, 2) ?>) to enrol.</p>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="space-y-4">
      <?= csrfField() ?>
      <?php if ($isEnrolled): ?>
        <label class="block text-xs font-bold uppercase text-neutral-600">Pay ahead (optional, Ksh)
          <input type="number" name="amount_ksh" min="1" max="<?= e((string) $left) ?>" step="1" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm" />
        </label>
        <p class="text-xs font-semibold" style="color:var(--ke-muted)">Money you pay ahead is used for your next modules before your wallet. Balance: Ksh <?= number_format($left, 2) ?>.</p>
      <?php endif; ?>
      <div class="flex flex-wrap gap-2">
        <?php if (!$isEnrolled): ?>
          <button type="submit" class="btn-primary" <?= $canEnrol ? '' : 'disabled' ?>>Enrol</button>
        <?php else: ?>
          <button type="submit" class="btn-primary">Pay ahead</button>
        <?php endif; ?>
        <a href="<?= url('/account/wallet') ?>" class="btn-secondary">Deposit / My wallet</a>
        <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary">Back to course</a>
      </div>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
