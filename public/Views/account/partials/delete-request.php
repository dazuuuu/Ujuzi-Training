<?php
/**
 * "Request deletion": organisations ask Super Admin, who deletes on approval.
 * Once asked, it shows as waiting — a request can't be sent twice or withdrawn.
 * Requires $drType, $drId, $drAction (the POST url) and $drWhat (e.g. "this course").
 */
$drPending = \App\Models\DeletionRequest::pendingFor($drType, (int) $drId);
?>
<?php if ($drPending): ?>
  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-black" style="background:#fffbeb;color:#92400e" title="Sent <?= e(date('j M Y', strtotime((string) $drPending['created_at']))) ?>">Delete requested — waiting for Super Admin</span>
<?php else: ?>
  <form method="post" action="<?= e($drAction) ?>" class="inline" onsubmit="var r = prompt('Why should <?= e(addslashes($drWhat)) ?> be deleted? Super Admin reviews the request — it can be sent only once and can\'t be withdrawn.'); if (r === null) return false; this.reason.value = r; return true;">
    <?= csrfField() ?><input type="hidden" name="reason" value="">
    <button type="submit" class="btn-danger" style="padding:0.3rem 0.65rem;font-size:0.75rem;">Request deletion</button>
  </form>
<?php endif; ?>
