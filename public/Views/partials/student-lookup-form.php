<?php /** Requires $number, $record and $formAction. */ ?>
<section>
  <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Students</p>
  <h1 class="mt-2 font-serif-heading text-3xl font-bold">Student lookup</h1>
  <p class="mt-1 text-sm font-medium text-neutral-600">Enter a registration number, e.g. UJ0012709/26, to see the student's details, courses and certificates — and check a certificate is genuine.</p>
</section>
<form method="get" action="<?= url($formAction) ?>" class="learn-card flex flex-col gap-2 p-4 sm:flex-row">
  <label for="reg" class="sr-only">Registration number</label>
  <input id="reg" name="reg" value="<?= e($number) ?>" required autocomplete="off" autocapitalize="characters" placeholder="Registration number" class="min-w-0 flex-1 rounded-lg border border-neutral-300 p-2.5 text-sm font-bold uppercase tracking-wide">
  <button type="submit" class="btn-primary"><?= icon('search', 'inline h-4 w-4 align-[-2px]') ?> Look up</button>
</form>
<?php if ($number !== '' && !$record): ?>
  <section class="flex items-start gap-3 rounded-xl border p-4" role="alert" style="border-color:var(--ke-red);background:#fef2f2;color:#991b1b">
    <?= icon('alert', 'h-6 w-6 shrink-0') ?>
    <div>
      <p class="font-black">Not found</p>
      <p class="text-sm font-semibold">No student has the registration number <?= e($number) ?>. Check it was typed exactly as printed; if it was, a certificate showing it is not genuine.</p>
    </div>
  </section>
<?php endif; ?>
