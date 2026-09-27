<?php
/** Requires $application (the student's own request) and $messages. */
use App\Models\AttachmentApplication;

require __DIR__ . '/../layout-header.php';

$status = (string) $application['status'];
$provider = $application['organisation_name'] ?: trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''));
$statusColor = in_array($status, ['rejected', 'paused'], true) ? 'var(--ke-red)' : 'var(--ke-green)';
$explain = [
    'pending' => 'Your request is waiting for the branch to review it.',
    'paused' => 'Your request is on hold. Read the latest note below — the branch may need something from you before deciding.',
    'accepted' => 'You have been accepted for attachment.',
    'recommended' => 'Your attachment is completed. Your recommendation letter is ready.',
    'completed' => 'Your attachment is completed.',
    'rejected' => 'This request was declined. You can send a new request for this course category.',
][$status] ?? '';
?>

<div class="space-y-6">
  <a href="<?= url('/account/attachment-providers') ?>" class="text-xs font-bold uppercase tracking-widest" style="color:var(--ke-green)">← My attachments</a>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-2">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-black uppercase tracking-widest text-neutral-500"><?= e($application['category_name'] ?? 'Attachment') ?></p>
        <h1 class="mt-1 font-serif-heading text-3xl font-bold"><?= e($provider) ?></h1>
        <?php if (!empty($application['branch_title'])): ?>
          <p class="text-sm font-semibold text-neutral-600"><?= e($application['branch_title']) ?><?= !empty($application['branch_location']) ? ' — ' . e($application['branch_location']) : '' ?></p>
        <?php endif; ?>
      </div>
      <span class="rounded-full border px-3 py-1 text-xs font-black uppercase" style="border-color:<?= $statusColor ?>;color:<?= $statusColor ?>">
        <?= e(AttachmentApplication::statusLabel($status)) ?>
      </span>
    </div>
    <p class="text-sm font-medium text-neutral-700"><?= e($explain) ?></p>
    <?php if ($status === 'recommended'): ?>
      <a href="<?= url('/account/recommendation-letter/' . (int) $application['id']) ?>" class="btn-primary inline-block" style="padding:0.45rem 0.9rem;">View recommendation letter</a>
    <?php endif; ?>
  </section>

  <section class="space-y-4">
    <h2 class="font-serif-heading text-lg font-bold">Notes</h2>
    <?php $viewerSide = 'student'; require __DIR__ . '/../partials/attachment-thread.php'; ?>

    <?php if ($status !== 'rejected'): ?>
      <form method="post" action="<?= url('/account/attachments/' . (int) $application['id'] . '/reply') ?>" class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm space-y-3">
        <?= csrfField() ?>
        <label class="block text-xs font-bold uppercase text-neutral-600" for="reply-body">Your reply</label>
        <textarea id="reply-body" name="body" rows="3" maxlength="4000" required class="w-full rounded-lg border border-neutral-300 p-3 text-sm" placeholder="Answer the branch's question, or tell them anything they should know."></textarea>
        <button type="submit" class="btn-primary">Send reply</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
