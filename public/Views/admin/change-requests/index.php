<?php
/** Requires $requests, $status in scope. */
require __DIR__ . '/../layout-header.php';
$labels = \App\Models\ChangeRequest::FIELDS;
?>
<div class="max-w-4xl space-y-3">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <p class="text-sm text-neutral-600">People can't edit their names, sign-in email or ID number themselves. Approve to apply the change; duplicates of another account are refused.</p>
    <nav class="flex gap-1 text-xs font-bold">
      <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Declined', '' => 'All'] as $key => $label): ?>
        <a href="<?= url('/admin/change-requests' . ($key !== 'pending' ? '?status=' . $key : '')) ?>" class="rounded-full border px-2.5 py-1 <?= $key === $status ? 'text-white' : 'text-neutral-600' ?>" style="<?= $key === $status ? 'background:var(--ke-green);border-color:var(--ke-green)' : 'border-color:var(--ke-line)' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>

  <?php if (!$requests): ?>
    <p class="rounded-lg border border-dashed p-4 text-sm text-neutral-500" style="border-color:var(--ke-line)">No requests here.</p>
  <?php endif; ?>

  <?php foreach ($requests as $r): ?>
    <article class="rounded-lg border bg-white p-3" style="border-color:var(--ke-line)">
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="text-sm font-bold"><a href="<?= url('/admin/users/' . (int) $r['user_id']) ?>" class="hover:underline"><?= e(trim($r['first_name'] . ' ' . $r['other_names'] . ' ' . $r['last_name']) ?: $r['email']) ?></a>
          <span class="font-normal text-neutral-500">· <?= e($r['role_name'] ?? '') ?><?= !empty($r['registration_number']) ? ' · ' . e($r['registration_number']) : '' ?></span></p>
        <p class="text-xs text-neutral-500"><?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?></p>
      </div>
      <table class="mt-2 w-full text-sm">
        <?php foreach ($r['changes'] as $key => $change): ?>
          <tr><td class="w-48 py-0.5 text-xs font-bold uppercase text-neutral-500"><?= e($labels[$key] ?? $key) ?></td><td class="py-0.5"><s class="text-neutral-400"><?= e($change['from'] ?: '—') ?></s> → <b><?= e($change['to']) ?></b></td></tr>
        <?php endforeach; ?>
      </table>
      <p class="mt-1 text-xs text-neutral-600"><b>Reason:</b> <?= e($r['reason'] ?? '') ?></p>
      <?php if ($r['status'] === 'pending'): ?>
        <form method="post" action="<?= url('/admin/change-requests/' . (int) $r['id']) ?>" class="mt-2 flex flex-wrap items-center gap-2">
          <?= csrfField() ?>
          <input name="note" maxlength="500" placeholder="Note to the person (optional)" class="min-w-0 flex-1 rounded-md border border-neutral-300 p-1.5 text-sm">
          <button name="decision" value="approved" class="btn-primary" style="padding:.4rem .8rem">Approve</button>
          <button name="decision" value="rejected" class="btn-secondary" style="padding:.4rem .8rem">Decline</button>
        </form>
      <?php else: ?>
        <p class="mt-1 text-xs font-bold" style="color:<?= $r['status'] === 'approved' ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $r['status'] === 'approved' ? 'Approved' : 'Declined' ?> <?= !empty($r['decided_at']) ? e(date('j M Y, H:i', strtotime($r['decided_at']))) : '' ?><?= !empty($r['admin_note']) ? ' — ' . e($r['admin_note']) : '' ?></p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
