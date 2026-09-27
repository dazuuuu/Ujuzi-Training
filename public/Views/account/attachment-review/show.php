<?php
/** Requires $application (detailed), $fees, $messages, $actions (action => label) and $backUrl. */
use App\Models\AttachmentApplication;

require __DIR__ . '/../layout-header.php';

$status = (string) $application['status'];
$studentName = trim($application['first_name'] . ' ' . $application['last_name']) ?: (string) $application['email'];
$statusColor = in_array($status, ['rejected', 'paused'], true) ? 'var(--ke-red)' : 'var(--ke-green)';
$when = static fn(?string $at): string => $at ? date('j M Y', strtotime($at)) : '—';
?>

<div class="space-y-6">
  <a href="<?= url($backUrl) ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← All attachees</a>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-500">Attachment request</p>
        <h1 class="mt-1 font-serif-heading text-3xl font-bold"><?= e($studentName) ?></h1>
        <p class="mt-1 text-sm font-semibold text-neutral-600">
          <?= e($application['email'] ?: '') ?><?= !empty($application['phone']) ? ' · ' . e($application['phone']) : '' ?>
        </p>
      </div>
      <span class="rounded-full border px-3 py-1 text-xs font-black uppercase" style="border-color:<?= $statusColor ?>;color:<?= $statusColor ?>">
        <?= e(AttachmentApplication::statusLabel($status)) ?>
      </span>
    </div>
    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Course category</dt><dd class="font-semibold"><?= e($application['category_name'] ?? '—') ?><?= !empty($application['category_organisation_name']) ? ' · ' . e($application['category_organisation_name']) : '' ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Branch</dt><dd class="font-semibold"><?= e($application['branch_title'] ?? 'Organisation (no branch)') ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500">Requested</dt><dd class="font-semibold"><?= e($when($application['selected_at'] ?? null)) ?></dd></div>
      <div><dt class="text-[11px] font-black uppercase text-neutral-500"><?= $status === 'recommended' ? 'Letter last sent' : 'Accepted' ?></dt><dd class="font-semibold"><?= e($when($status === 'recommended' ? ($application['letter_sent_at'] ?? $application['recommended_at']) : ($application['accepted_at'] ?? null))) ?></dd></div>
    </dl>
  </section>

  <div class="grid gap-6 lg:grid-cols-3">
    <section class="lg:col-span-1 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Fees &amp; course progress</h2>
      <?php require __DIR__ . '/../partials/student-standing.php'; ?>
    </section>

    <section class="lg:col-span-2 space-y-4">
      <h2 class="font-serif-heading text-lg font-bold">Notes with the student</h2>
      <?php $viewerSide = 'reviewer'; require __DIR__ . '/../partials/attachment-thread.php'; ?>

      <?php if ($actions || !in_array($status, ['rejected'], true)): ?>
        <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/respond') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
          <?= csrfField() ?>
          <label class="block text-xs font-bold uppercase text-neutral-600" for="review-body">Note to the student</label>
          <textarea id="review-body" name="body" rows="4" maxlength="4000" class="w-full rounded-lg border border-neutral-300 p-3 text-sm"
                    placeholder="e.g. Please confirm you have completed the safety module and bring your ID on your first day."></textarea>

          <?php if ($actions): ?>
            <fieldset>
              <legend class="text-xs font-bold uppercase text-neutral-600">Status</legend>
              <div class="mt-2 flex flex-wrap gap-2">
                <label class="flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold">
                  <input type="radio" name="action" value="" checked> Keep as <?= e(AttachmentApplication::statusLabel($status)) ?>
                </label>
                <?php foreach ($actions as $value => $label):
                  $blocked = $value === 'complete' && $fees && !$fees['is_settled'];
                ?>
                  <label class="flex items-center gap-2 rounded-lg border border-neutral-300 px-3 py-2 text-sm font-semibold <?= $blocked ? 'opacity-50' : '' ?>" <?= $blocked ? 'title="Clear the course-fee balance first"' : '' ?>>
                    <input type="radio" name="action" value="<?= e($value) ?>" <?= $blocked ? 'disabled' : '' ?>> <?= e($label) ?>
                  </label>
                <?php endforeach; ?>
              </div>
              <p class="mt-2 text-xs font-semibold text-neutral-500">You can send a note without changing the status. Put on hold while you wait for the student — for example, to confirm they meet your requirements.</p>
            </fieldset>
          <?php endif; ?>

          <button type="submit" class="btn-primary">Send</button>
        </form>
      <?php endif; ?>

      <?php if ($status === 'recommended'): ?>
        <form method="post" action="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/resend-letter') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3" onsubmit="return confirm('Send the recommendation letter to this student again?');">
          <?= csrfField() ?>
          <h3 class="font-bold text-gray-800">Resend recommendation letter</h3>
          <p class="text-xs font-semibold text-neutral-500">The student gets the letter link in their portal and, when email is set up, by email.</p>
          <textarea name="body" rows="2" maxlength="4000" class="w-full rounded-lg border border-neutral-300 p-3 text-sm" placeholder="Optional message to go with it"></textarea>
          <button type="submit" class="btn-secondary">Resend letter</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
