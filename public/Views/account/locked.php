<?php
/** A locked account fixes its email / phone. Requires $mailReady. */
require __DIR__ . '/layout-header.php';
$pending = (string) ($currentUser['pending_email'] ?? '');
?>
<div class="mx-auto max-w-xl space-y-5">
  <section class="rounded-xl border-l-4 bg-white p-5 shadow-sm" style="border-color:#f59e0b">
    <p class="text-xs font-black uppercase tracking-widest" style="color:#92400e">Account locked</p>
    <h1 class="mt-1 font-serif-heading text-2xl font-bold">Please confirm your own details</h1>
    <p class="mt-2 text-sm font-medium text-neutral-700"><?= e($currentUser['lock_reason'] ?? 'Another account uses the same details.') ?> Every account needs its own email and phone number, because people sign in with either. Give yours below and verify the email — your account unlocks straight away.</p>
  </section>

  <?php if ($pending !== ''): ?>
    <p class="rounded-xl border p-4 text-sm font-semibold" style="border-color:#b7e1c7;background:#ecf7f0;color:var(--ke-green)">
      We sent a link to <strong><?= e($pending) ?></strong>. Open that email and press <strong>Verify my email</strong>, then come back here. Didn't get it? Check spam, or send it again below.
    </p>
  <?php endif; ?>
  <?php if (!$mailReady): ?>
    <p class="rounded-xl border p-4 text-sm font-semibold" style="border-color:#fecaca;background:#fef2f2;color:var(--ke-red)">Email is not set up on this system yet, so the link can't be sent. Ask Super Admin to unlock your account.</p>
  <?php endif; ?>

  <form method="post" action="<?= url('/account/locked') ?>" class="space-y-4 rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)">
    <?= csrfField() ?>
    <label class="block text-[11px] font-bold uppercase text-neutral-600">Your email address
      <input type="email" name="email" required value="<?= e($pending ?: ($currentUser['email'] ?? '')) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm normal-case"></label>
    <label class="block text-[11px] font-bold uppercase text-neutral-600">Your phone number<?= str_contains((string) ($currentUser['lock_reason'] ?? ''), 'phone') ? ' (must be yours only)' : ' (optional)' ?>
      <input name="phone" inputmode="tel" value="<?= e($currentUser['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm normal-case"></label>
    <div class="flex flex-wrap items-center gap-3">
      <button type="submit" class="btn-primary"><?= $pending !== '' ? 'Send the link again' : 'Send verification link' ?></button>
      <a href="<?= url('/account/locked') ?>" class="text-sm font-bold underline" style="color:var(--ke-green)">I've verified — continue</a>
      <a href="<?= url('/account/logout') ?>" class="ml-auto text-sm font-bold text-neutral-500 underline">Sign out</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/layout-footer.php'; ?>
