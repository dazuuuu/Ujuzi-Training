<?php
/**
 * One attachee as a sheet row. Requires $application, $n, $studentName, $when,
 * $searchText, $unreadNotes; $completedRow for the completed list.
 * Fees and progress are for the course this request is for only.
 */
$status = (string) ($application['status'] ?? 'pending');
$waiting = in_array($status, ['pending', 'paused'], true);
$rf = $application['request_fees'] ?? null;
$item = null;
foreach (($application['fees']['items'] ?? []) as $i) {
    if ((int) $i['course_id'] === (int) ($application['course_id'] ?? 0)) {
        $item = $i;
    }
}
$sheet = \App\Models\AttachmentAssessment::forApplication((int) $application['id']);
$newNotes = (int) ($unreadNotes[(int) $application['id']] ?? 0);
$small = 'padding:.15rem .45rem;font-size:.7rem';
?>
<tr data-search="<?= e($searchText($application)) ?>"<?= !empty($rf['below_minimum']) ? ' style="background:#fffbeb"' : '' ?>>
  <td><?php if ($waiting && empty($completedRow)): ?><input type="checkbox" name="ids[]" value="<?= (int) $application['id'] ?>" form="bulk-accept-form" class="bulk-pick" aria-label="Select <?= e($studentName($application)) ?>"><?php endif; ?></td>
  <td><?= $n + 1 ?></td>
  <td class="font-bold"><?= e($studentName($application)) ?></td>
  <td><?= e($application['registration_number'] ?? '') ?></td>
  <td><?php if (!empty($application['phone'])): ?><a class="underline" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $application['phone'])) ?>"><?= e($application['phone']) ?></a><?php endif; ?></td>
  <td><?= e($application['course_title'] ?? '—') ?></td>
  <td><?= e($application['category_name'] ?? '—') ?></td>
  <td><?= e($application['branch_title'] ?? '—') ?></td>
  <td><?= e($when(!empty($completedRow) ? ($application['recommended_at'] ?? ($application['completed_at'] ?? null)) : ($application['selected_at'] ?? null))) ?></td>
  <td><?php if ($rf && $rf['fee'] > 0): ?><span style="color:<?= $rf['balance'] > 0 ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= number_format($rf['paid']) ?> / <?= number_format($rf['fee']) ?></span><?php elseif ($rf): ?>Free<?php else: ?>—<?php endif; ?></td>
  <td><?= $item ? (int) $item['modules_passed'] . '/' . (int) $item['modules_total'] . ' modules' . ($item['final_passed'] ? ' · exam ✓' : '') : '—' ?></td>
  <td><?= !empty($sheet['assessed_at']) ? '<span style="color:var(--ke-green)">' . \App\Models\AttachmentAssessment::grade((float) $sheet['percent']) . ' · ' . rtrim(rtrim(number_format((float) $sheet['percent'], 1), '0'), '.') . '%</span>' : ($sheet ? 'In progress' : '—') ?></td>
  <td><span style="color:<?= $waiting ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= e(!empty($completedRow) ? 'Letter ready' : \App\Models\AttachmentApplication::statusLabel($status)) ?></span></td>
  <td style="white-space:nowrap">
    <span class="inline-flex flex-wrap items-center gap-1">
      <a href="<?= url('/account/attachment-requests/' . (int) $application['id']) ?>" class="btn-secondary" style="<?= $small ?>">Open<?= $newNotes ? ' · ' . $newNotes . ' new' : '' ?></a>
      <a href="<?= url('/account/attachment-requests/' . (int) $application['id'] . '/assessment') ?>" class="btn-secondary" style="<?= $small ?>">Assessment</a>
      <?php if (empty($completedRow) && $status === 'pending'): ?>
        <form method="post" action="<?= url('/account/branch-admin/' . (int) $application['id'] . '/accept') ?>"><?= csrfField() ?><button class="btn-primary" style="<?= $small ?>">Accept</button></form>
      <?php endif; ?>
      <?php if (empty($completedRow) && in_array($status, ['pending', 'paused', 'accepted'], true)): ?>
        <form method="post" action="<?= url('/account/branch-admin/' . (int) $application['id'] . '/complete') ?>" onsubmit="return confirm('Mark this attachment complete? This sends the assessment to the course organisation and generates their recommendation letter.');"><?= csrfField() ?><button class="btn-primary" style="<?= $small ?>">Complete</button></form>
      <?php endif; ?>
      <?php if (empty($completedRow) && in_array($status, ['pending', 'paused', 'accepted'], true)): $rejectId = (int) $application['id']; require __DIR__ . '/../partials/reject-request.php'; endif; ?>
    </span>
  </td>
</tr>
