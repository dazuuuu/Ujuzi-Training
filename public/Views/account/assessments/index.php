<?php
/** Attachment assessments shared with this organisation. Requires $sheets and $open (one sheet to show, or null). */
require __DIR__ . '/../layout-header.php';
$pct = static fn($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
?>
<div class="space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachment assessments</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">How your students did on attachment, marked by the organisation they were attached to. Each sheet arrives when the attachment is completed; its grade counts with the course's final exam.</p>
  </section>

  <?php if ($open): ?>
    <section class="rounded-xl border bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-green)">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <h2 class="font-serif-heading text-xl font-bold"><?= e($open['student_name']) ?> <span class="text-sm font-bold text-neutral-500"><?= e($open['registration_number'] ?? '') ?></span></h2>
          <p class="text-xs font-semibold text-neutral-500"><?= e($open['course_title']) ?> · at <?= e($open['provider_name']) ?><?= !empty($open['branch_title']) ? ' (' . e($open['branch_title']) . ')' : '' ?> · received <?= e(date('j M Y', strtotime((string) $open['shared_at']))) ?></p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-black" style="background:#ecf7f0;color:var(--ke-green)"><?= rtrim(rtrim(number_format((float) $open['total_score'], 1), '0'), '.') ?> / <?= rtrim(rtrim(number_format((float) $open['max_score'], 1), '0'), '.') ?> · <?= $pct($open['percent']) ?></span>
      </div>
      <table class="excel-table admin-data-table">
        <thead><tr><th>Criterion</th><th>Mark</th><th>Out of</th><th>Comment</th></tr></thead>
        <tbody>
          <?php foreach ($open['items'] as $item): ?>
            <tr><td class="font-bold"><?= e($item['criterion']) ?></td><td><?= $item['score'] === null ? '—' : e((string) (float) $item['score']) ?></td><td><?= e((string) (float) $item['max']) ?></td><td style="white-space:normal"><?= e($item['comment'] ?? '') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!empty($open['remarks'])): ?><p class="text-sm text-neutral-700"><strong>Remarks:</strong> <?= nl2br(e($open['remarks'])) ?></p><?php endif; ?>
      <div class="flex gap-2 print:hidden"><button type="button" class="btn-secondary" onclick="window.print()">Print</button><a href="<?= url('/account/assessments') ?>" class="btn-secondary">Close</a></div>
    </section>
  <?php endif; ?>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm print:hidden">
    <table class="excel-table admin-data-table">
      <thead><tr><th>Student</th><th>Reg. No.</th><th>Course</th><th>Attached at</th><th>Grade</th><th>Received</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sheets as $sheet): ?>
          <tr>
            <td class="font-black"><?= e($sheet['student_name']) ?></td>
            <td class="font-mono"><?= e($sheet['registration_number'] ?? '') ?></td>
            <td><?= e($sheet['course_title']) ?></td>
            <td><?= e($sheet['provider_name']) ?><?= !empty($sheet['branch_title']) ? ' · ' . e($sheet['branch_title']) : '' ?></td>
            <td class="font-black" style="color:<?= (float) $sheet['percent'] >= 50 ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $pct($sheet['percent']) ?></td>
            <td><?= e(date('j M Y', strtotime((string) $sheet['shared_at']))) ?></td>
            <td><a href="<?= url('/account/assessments?sheet=' . (int) $sheet['id']) ?>" class="btn-secondary" style="padding:.25rem .6rem;font-size:.72rem">Open sheet</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$sheets): ?><tr><td colspan="7" class="text-center font-bold text-neutral-500">No assessments yet. They arrive as your students complete their attachments.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
