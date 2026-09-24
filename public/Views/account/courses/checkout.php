<?php
$course = $course ?? [];
$provider = $paymentProvider ?? 'mpesa';
$paymentsEnabled = !empty($paymentsEnabled);
$rate = (float) ($kshUsdRate ?? 130);
$amountKsh = max(0, (float) ($course['enrollment_fee_ksh'] ?? 0));
$amountUsd = $rate > 0 ? $amountKsh / $rate : 0;
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-3xl space-y-6">
  <section class="rounded-xl border bg-white p-6 shadow-sm space-y-4" style="border-color:var(--ke-line)">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Checkout</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= e($course['title'] ?? 'Course') ?></h1>
      <p class="mt-1 text-sm font-semibold" style="color:var(--ke-muted)"><?= e($course['organisation_name'] ?? '') ?> · Tutor: <?= e(trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')) ?: ($course['email'] ?? '')) ?></p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Enrollment fee</p>
        <p class="mt-2 text-2xl font-black">Ksh <?= number_format($amountKsh, 2) ?></p>
      </div>
      <div class="rounded-lg border p-4" style="border-color:var(--ke-line)">
        <p class="text-[11px] font-black uppercase" style="color:var(--ke-muted)">Payment method</p>
        <p class="mt-2 text-lg font-black"><?= !$paymentsEnabled ? 'Payments closed' : ($provider === 'stripe' ? 'Stripe sandbox' : 'M-Pesa Daraja') ?></p>
        <?php if (!$paymentsEnabled): ?>
          <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Testing mode is active. Submitting will enroll you without charging.</p>
        <?php elseif ($provider === 'stripe'): ?>
          <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Estimated charge: $<?= number_format($amountUsd, 2) ?> at Ksh <?= number_format($rate, 2) ?> per USD.</p>
        <?php else: ?>
          <p class="mt-1 text-xs font-semibold" style="color:var(--ke-muted)">Daraja integration placeholder. Sandbox confirmation will enroll you now.</p>
        <?php endif; ?>
      </div>
    </div>

    <form method="post" action="<?= url('/account/courses/' . (int) $course['id'] . '/checkout') ?>" class="space-y-4">
      <?= csrfField() ?>
      <label class="flex items-start gap-2 text-sm font-semibold">
        <input type="checkbox" required class="mt-1 h-4 w-4" />
        <span>I confirm this course, tutor, organisation, and enrollment fee.</span>
      </label>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="btn-primary"><?= !$paymentsEnabled ? 'Enroll for testing' : ($provider === 'stripe' ? 'Confirm Stripe sandbox payment' : 'Confirm M-Pesa sandbox payment') ?></button>
        <a href="<?= url('/account/courses/' . (int) $course['id']) ?>" class="btn-secondary">Back to course</a>
      </div>
    </form>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
