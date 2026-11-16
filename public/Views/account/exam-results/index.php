<?php /** Requires $courses (id => row), $courseId, $rows. */ require __DIR__ . '/../layout-header.php';
$passed = count(array_filter($rows, static fn(array $r): bool => !empty($r['passed']))); ?>
<div class="space-y-4">
  <div>
    <h1 class="font-serif-heading text-3xl font-bold">Exam results</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Exam-only courses are marked automatically. Pick an exam to see who passed and who failed.</p>
  </div>
  <form method="get" class="flex flex-wrap gap-2">
    <select name="course" class="rounded-lg border border-neutral-300 p-2 text-sm" onchange="this.form.submit()">
      <option value="">Choose an exam…</option>
      <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $courseId ? 'selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?>
    </select>
    <?php if ($courseId): ?>
      <a class="btn-primary" href="<?= url('/account/exam-results?course=' . $courseId . '&export=xlsx') ?>">Excel</a>
      <a class="btn-secondary" target="_blank" href="<?= url('/account/exam-results?course=' . $courseId . '&export=pdf') ?>">PDF</a>
    <?php endif; ?>
  </form>
  <?php if (!$courses): ?><p class="rounded-xl border border-dashed p-6 text-sm font-semibold text-neutral-500">No exam-only courses yet. When creating a course, tick “Exam only”.</p><?php endif; ?>
  <?php if ($courseId): ?>
    <p class="text-sm font-bold"><?= count($rows) ?> candidates · <span style="color:#166534"><?= $passed ?> passed</span> · <span style="color:#b91c1c"><?= count($rows) - $passed ?> failed</span></p>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white">
      <table class="excel-table admin-data-table">
        <thead><tr><th>Reg. No.</th><th>Name</th><th>Phone</th><th>Score</th><th>Result</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr><td class="font-mono"><?= e($r['registration_number']) ?></td><td class="font-black"><?= e(trim($r['first_name'] . ' ' . $r['last_name'])) ?></td><td><?= e($r['phone']) ?></td>
            <td><?= (int) $r['score'] ?>%</td><td><?= $r['passed'] ? '<b style="color:#166534">PASSED</b>' : '<b style="color:#b91c1c">FAILED</b>' ?></td>
            <td><?= e(date('j M Y', strtotime((string) ($r['completed_at'] ?: $r['updated_at'])))) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="text-center font-bold text-neutral-500">Nobody has taken this exam yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
