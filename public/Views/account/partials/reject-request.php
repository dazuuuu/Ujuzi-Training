<?php
/**
 * "Reject" with a reason the student will see. Requires $rejectId (the
 * attachment request id). Posts to the request's respond action and comes
 * back to the list.
 */
$presets = [
    'Please pay at least 50% of your school fees, then send your request again.',
    'Please clear your course balance, then send your request again.',
    'We are full for this intake. Please try another branch or organisation.',
    'You do not yet meet our requirements for this attachment.',
];
?>
<details class="reject-request">
  <summary class="btn-danger" style="padding:0.25rem 0.5rem;font-size:0.7rem;list-style:none;cursor:pointer;">Reject</summary>
  <form method="post" action="<?= url('/account/attachment-requests/' . (int) $rejectId . '/respond') ?>" class="reject-request-panel space-y-2">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="decline">
    <input type="hidden" name="return" value="list">
    <label class="block text-[11px] font-black uppercase text-neutral-600" for="reject-<?= (int) $rejectId ?>">Reason the student will see</label>
    <textarea id="reject-<?= (int) $rejectId ?>" name="body" rows="3" required maxlength="4000" class="w-full rounded-lg border border-neutral-300 p-2 text-xs normal-case" placeholder="e.g. Please pay at least 50% of your school fees…"></textarea>
    <div class="flex flex-wrap gap-1">
      <?php foreach ($presets as $preset): ?>
        <button type="button" class="reject-preset" data-text="<?= e($preset) ?>"><?= e(mb_strimwidth($preset, 0, 34, '…')) ?></button>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn-danger" style="padding:0.3rem 0.6rem;font-size:0.72rem;">Reject request</button>
  </form>
</details>
