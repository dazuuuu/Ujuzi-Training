<?php
/** Delete requests from organisations. Requires $pending, $recent and $types. */
require __DIR__ . '/../layout-header.php';
$typeLabel = static fn(string $t): string => $types[$t][0] ?? ucfirst($t);
?>
<div class="max-w-5xl space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisations</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Delete requests</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Organisations can't delete things themselves — they ask here. Approving deletes it straight away. Each request can be sent only once and can't be withdrawn.</p>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-table admin-data-table">
      <thead><tr><th>What</th><th>Type</th><th>Organisation</th><th>Asked by</th><th>Reason</th><th>Sent</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($pending as $r): ?>
          <tr>
            <td class="font-black" style="white-space:normal"><?= e($r['label']) ?></td>
            <td><?= e($typeLabel($r['entity_type'])) ?></td>
            <td><?= e($r['organisation_name'] ?: '—') ?></td>
            <td><?= e(trim($r['first_name'] . ' ' . $r['last_name']) ?: $r['email']) ?><span class="block text-[10px] text-neutral-500"><?= e($r['role_name']) ?> · <?= e($r['email'] ?: $r['phone']) ?></span></td>
            <td style="white-space:normal"><?= e($r['reason'] ?: '—') ?></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $r['created_at']))) ?></td>
            <td>
              <form method="post" action="<?= url('/admin/delete-requests/' . (int) $r['id']) ?>" class="flex gap-1">
                <?= csrfField() ?>
                <button type="submit" name="decision" value="approved" class="btn-danger" style="padding:.25rem .6rem;font-size:.72rem" onclick="return confirm('Delete <?= e(addslashes($r['label'])) ?> for good?');">Approve &amp; delete</button>
                <button type="submit" name="decision" value="rejected" class="btn-secondary" style="padding:.25rem .6rem;font-size:.72rem">Decline</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$pending): ?><tr><td colspan="7" class="text-center font-bold text-neutral-500">No delete requests waiting.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($recent): ?>
    <section class="space-y-2">
      <h2 class="font-serif-heading text-lg font-bold">Decided</h2>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-table admin-data-table">
          <thead><tr><th>What</th><th>Type</th><th>Asked by</th><th>Decision</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td><?= e($r['label']) ?></td><td><?= e($typeLabel($r['entity_type'])) ?></td>
                <td><?= e(trim($r['first_name'] . ' ' . $r['last_name']) ?: $r['email']) ?></td>
                <td class="font-black" style="color:<?= $r['status'] === 'approved' ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= $r['status'] === 'approved' ? 'Deleted' : 'Declined' ?></td>
                <td><?= $r['decided_at'] ? e(date('j M Y H:i', strtotime((string) $r['decided_at']))) : '' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
