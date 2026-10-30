<?php
/** Attachment assessments shared with this organisation. Requires $sheets and $open (one sheet to show, or null). */
require __DIR__ . '/../layout-header.php';
$pct = static fn($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
/** Final exam + attachment assessment, split as Super Admin sets in Settings. */
$gradeOf = static fn(array $sheet): array => \App\Models\Course::finalGrade(['id' => (int) $sheet['course_id'], 'requires_attachment' => 1], (int) $sheet['student_user_id']);
?>
<div class="space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachment</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachment assessments</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">How your students did on attachment, logged day by day by the organisation they were attached to. Each sheet arrives when the attachment is completed. Final grade = <?= \App\Models\Course::examPercent() ?>% final exam + <?= 100 - \App\Models\Course::examPercent() ?>% attachment assessment.</p>
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
      <?php [$days, $answers, $criteria] = \App\Models\AttachmentAssessment::parts($open); ?>
      <?php if ($answers): ?>
        <table class="excel-table admin-data-table">
          <thead><tr><th>#</th><th>Assessment question</th><th>Answer</th></tr></thead>
          <tbody>
            <?php foreach ($answers as $n => $a): ?>
              <tr><td><?= $n + 1 ?></td><td style="white-space:normal"><?= e($a['question']) ?></td><td class="font-bold" style="white-space:normal"><?= $a['value'] === null ? '—' : ($a['kind'] === 'score' ? e((string) (float) $a['value']) . ' / ' . (int) $a['max'] : ($a['kind'] === 'yesno' ? ucfirst((string) $a['value']) : nl2br(e((string) $a['value'])))) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <?php if ($days): ?>
        <table class="excel-table admin-data-table">
          <thead><tr><th>Day</th><th>Date</th><th>What they did</th><th>Score /<?= \App\Models\AttachmentAssessment::DAY_MAX ?></th><th>Remarks</th></tr></thead>
          <tbody>
            <?php foreach ($days as $n => $item): ?>
              <tr><td><?= $n + 1 ?></td><td style="white-space:nowrap"><?= e(date('D j M Y', strtotime((string) $item['date']))) ?></td><td style="white-space:normal"><?= nl2br(e($item['activities'] ?? '')) ?></td><td class="font-bold"><?= e((string) (float) $item['score']) ?></td><td style="white-space:normal"><?= e($item['remarks'] ?? '') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <?php if ($criteria): ?>
        <table class="excel-table admin-data-table">
          <thead><tr><th>Criterion</th><th>Mark</th><th>Out of</th><th>Comment</th></tr></thead>
          <tbody>
            <?php foreach ($criteria as $item): ?>
              <tr><td class="font-bold"><?= e($item['criterion']) ?></td><td><?= ($item['score'] ?? null) === null ? '—' : e((string) (float) $item['score']) ?></td><td><?= e((string) (float) ($item['max'] ?? 0)) ?></td><td style="white-space:normal"><?= e($item['comment'] ?? '') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <p class="text-sm font-black">Attachment grade: <?= \App\Models\AttachmentAssessment::grade((float) $open['percent']) ?> (<?= $pct($open['percent']) ?>)<?= $days ? ' · ' . count($days) . ' day' . (count($days) === 1 ? '' : 's') . ' attended' : '' ?></p>
      <?php if (!empty($open['remarks'])): ?><p class="text-sm text-neutral-700"><strong>Closing remark:</strong> <?= nl2br(e($open['remarks'])) ?></p><?php endif; ?>
      <div class="flex gap-2 print:hidden"><button type="button" class="btn-secondary" onclick="window.print()">Print</button><a href="<?= url('/account/assessments') ?>" class="btn-secondary">Close</a></div>
    </section>
  <?php endif; ?>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm print:hidden">
    <table class="excel-table admin-data-table">
      <thead><tr><th>Student</th><th>Reg. No.</th><th>Course</th><th>Attached at</th><th>Attachment</th><th>Final exam</th><th>Final grade (90/10)</th><th>Received</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sheets as $sheet): ?>
          <tr>
            <td class="font-black"><?= e($sheet['student_name']) ?></td>
            <td class="font-mono"><?= e($sheet['registration_number'] ?? '') ?></td>
            <td><?= e($sheet['course_title']) ?></td>
            <td><?= e($sheet['provider_name']) ?><?= !empty($sheet['branch_title']) ? ' · ' . e($sheet['branch_title']) : '' ?></td>
            <td class="font-black" style="color:<?= (float) $sheet['percent'] >= 50 ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= $pct($sheet['percent']) ?></td>
            <?php $g = $gradeOf($sheet); ?>
            <td><?= $g['exam'] !== null ? $pct($g['exam']) : '—' ?></td>
            <td class="font-black"><?= $g['grade'] !== null ? $pct($g['grade']) : '<span class="text-xs font-semibold text-neutral-500">after the exam</span>' ?></td>
            <td><?= e(date('j M Y', strtotime((string) $sheet['shared_at']))) ?></td>
            <td><a href="<?= url('/account/assessments?sheet=' . (int) $sheet['id']) ?>" class="btn-secondary" style="padding:.25rem .6rem;font-size:.72rem">Open sheet</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$sheets): ?><tr><td colspan="9" class="text-center font-bold text-neutral-500">No assessments yet. They arrive as your students complete their attachments.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
