<?php
/**
 * A course branch admin's students. Requires $tab, $branches, $students,
 * $enrolments, $from, $to and $problems (rows skipped by the last upload).
 */
require __DIR__ . '/../layout-header.php';
$dateQs = http_build_query(array_filter(['from' => $from, 'to' => $to]));
$tabUrl = static fn(string $t): string => url('/account/branch-students?tab=' . $t);
$exportBar = static function (string $list) use ($dateQs): void {
    $qs = $list === 'enrolments' && $dateQs !== '' ? '?' . $dateQs : '';
    echo '<span class="flex flex-wrap gap-2 print:hidden">'
        . '<a class="btn-primary inline-flex items-center gap-2" href="' . e(url('/account/branch-students/export/' . $list . '/xlsx') . $qs) . '">' . icon('download', 'h-4 w-4') . ' Excel</a>'
        . '<a class="btn-secondary inline-flex items-center gap-2" target="_blank" rel="noopener" href="' . e(url('/account/branch-students/export/' . $list . '/pdf') . $qs) . '">' . icon('file', 'h-4 w-4') . ' View PDF</a>'
        . '<button type="button" class="btn-secondary" onclick="window.print()">Print</button></span>';
};
?>
<div class="space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Branch admin</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Students</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600"><?= e(implode(', ', array_column($branches, 'title'))) ?> — your students, their enrolments and fees, and adding new students.</p>
  </section>

  <nav class="course-tabs print:hidden" aria-label="Students">
    <a href="<?= $tabUrl('students') ?>" role="tab" aria-selected="<?= $tab === 'students' ? 'true' : 'false' ?>" style="text-decoration:none">Students (<?= count($students) ?>)</a>
    <a href="<?= $tabUrl('tutors') ?>" role="tab" aria-selected="<?= $tab === 'tutors' ? 'true' : 'false' ?>" style="text-decoration:none">Tutors (<?= count($tutors) ?>)</a>
    <a href="<?= $tabUrl('enrolments') ?>" role="tab" aria-selected="<?= $tab === 'enrolments' ? 'true' : 'false' ?>" style="text-decoration:none">Enrolment records</a>
    <a href="<?= url('/account/wallet') ?>" role="tab" aria-selected="false" style="text-decoration:none">Financial records</a>
    <a href="<?= $tabUrl('add') ?>" role="tab" aria-selected="<?= $tab === 'add' ? 'true' : 'false' ?>" style="text-decoration:none">Add students &amp; tutors</a>
  </nav>

  <?php if ($tab === 'students'): ?>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <input type="search" id="bs-search" placeholder="Search name, reg. no., phone or email…" class="w-full sm:max-w-sm rounded-lg border border-neutral-300 p-2 text-sm print:hidden">
      <?php $exportBar('students'); ?>
    </div>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table" id="bs-table">
        <thead><tr><th>Reg. No.</th><th>Name</th><th>Phone</th><th>Email</th><th>Branch</th><th>Courses</th><th>Joined</th></tr></thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr data-search="<?= e(strtolower($s['name'] . ' ' . $s['registration_number'] . ' ' . $s['phone'] . ' ' . $s['email'])) ?>">
              <td class="font-mono"><?= e($s['registration_number']) ?></td>
              <td class="font-black"><?= e($s['name']) ?></td>
              <td><?= $s['phone'] !== '' ? '<a class="underline" href="tel:' . e(preg_replace('/[^0-9+]/', '', $s['phone'])) . '">' . e($s['phone']) . '</a>' : '—' ?></td>
              <td><?= e($s['email'] ?: '—') ?></td>
              <td><?= e($s['branch_title'] ?? '') ?></td>
              <td><?= (int) $s['courses'] ?></td>
              <td><?= $s['joined_at'] ? e(date('j M Y', strtotime($s['joined_at']))) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$students): ?><tr><td colspan="7" class="text-center font-bold text-neutral-500">No students in your branch yet. Add them under "Add students".</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <script>
      document.getElementById('bs-search').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('#bs-table tbody tr[data-search]').forEach(function (tr) { tr.hidden = q !== '' && tr.dataset.search.indexOf(q) === -1; });
      });
    </script>

  <?php elseif ($tab === 'tutors'): ?>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Branch</th><th>Joined</th></tr></thead>
        <tbody>
          <?php foreach ($tutors as $t): ?>
            <tr>
              <td class="font-black"><?= e($t['name']) ?></td>
              <td><?= e($t['phone'] ?: '—') ?></td>
              <td><?= e($t['email'] ?: '—') ?></td>
              <td><?= e($t['branch_title'] ?? '') ?></td>
              <td><?= $t['joined_at'] ? e(date('j M Y', strtotime($t['joined_at']))) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$tutors): ?><tr><td colspan="5" class="text-center font-bold text-neutral-500">No tutors in your branch yet. Add them under "Add students &amp; tutors".</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php elseif ($tab === 'enrolments'): ?>
    <div class="flex flex-wrap items-end justify-between gap-2">
      <form method="get" action="<?= url('/account/branch-students') ?>" class="flex flex-wrap items-end gap-2 print:hidden">
        <input type="hidden" name="tab" value="enrolments">
        <label class="text-xs font-bold uppercase text-neutral-600">From<input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
        <label class="text-xs font-bold uppercase text-neutral-600">To<input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
        <button type="submit" class="btn-secondary">Show</button>
      </form>
      <?php $exportBar('enrolments'); ?>
    </div>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead><tr><th>Enrolled</th><th>Student</th><th>Reg. No.</th><th>Course</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Progress</th></tr></thead>
        <tbody>
          <?php foreach ($enrolments as $r): ?>
            <tr>
              <td><?= $r['enrolled_at'] ? e(date('j M Y', strtotime($r['enrolled_at']))) : '—' ?></td>
              <td class="font-black"><?= e($r['name']) ?></td>
              <td class="font-mono"><?= e($r['registration_number']) ?></td>
              <td><?= e($r['course_title']) ?></td>
              <td>Ksh <?= number_format((float) $r['fee'], 2) ?></td>
              <td>Ksh <?= number_format((float) $r['paid'], 2) ?></td>
              <td style="color:<?= (float) $r['balance'] > 0 ? 'var(--ke-red)' : 'var(--ke-green)' ?>">Ksh <?= number_format((float) $r['balance'], 2) ?></td>
              <td><?= (int) $r['progress'] ?>%</td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$enrolments): ?><tr><td colspan="8" class="text-center font-bold text-neutral-500">No enrolments for these dates.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <?php if ($problems): ?>
      <section class="rounded-xl border p-4 text-sm" style="border-color:#f59e0b;background:#fffbeb;color:#92400e">
        <p class="font-black">Rows not added</p>
        <ul class="mt-2 list-disc pl-5 text-xs font-semibold"><?php foreach ($problems as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
      </section>
    <?php endif; ?>
    <div class="grid gap-5 lg:grid-cols-2">
      <section class="rounded-xl border bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-line)">
        <h2 class="font-serif-heading text-lg font-bold">Add one person</h2>
        <form method="post" action="<?= url('/account/branch-students') ?>" class="space-y-3">
          <?= csrfField() ?>
          <label class="block text-[11px] font-bold uppercase text-neutral-600">Adding a<select name="role" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><option value="student">Student</option><option value="trainer">Tutor (teaches courses)</option></select></label>
          <div class="grid gap-3 sm:grid-cols-3">
            <label class="text-[11px] font-bold uppercase text-neutral-600">First name *<input name="first_name" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Other names<input name="other_names" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Last name *<input name="last_name" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <label class="text-[11px] font-bold uppercase text-neutral-600">Email<input type="email" name="email" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
            <label class="text-[11px] font-bold uppercase text-neutral-600">Phone<input name="phone" inputmode="tel" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"></label>
          </div>
          <?php if (count($branches) > 1): ?>
            <label class="block text-[11px] font-bold uppercase text-neutral-600">Branch<select name="branch_id" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><?php foreach ($branches as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['title']) ?></option><?php endforeach; ?></select></label>
          <?php endif; ?>
          <p class="field-hint">They sign in with the email or phone and the default password, then choose their own.</p>
          <button type="submit" class="btn-primary">Add</button>
        </form>
      </section>
      <section class="rounded-xl border bg-white p-5 shadow-sm space-y-3" style="border-color:var(--ke-line)">
        <h2 class="font-serif-heading text-lg font-bold">Upload many</h2>
        <ol class="list-decimal pl-5 text-sm font-medium text-neutral-700 space-y-1">
          <li><a href="<?= url('/account/branch-students/template') ?>" class="font-black underline" style="color:var(--ke-green)">Download the template</a> (opens in Excel).</li>
          <li>Fill one student per row: first name, other names, last name, email, phone.</li>
          <li>Save as CSV (File → Save As → CSV) and upload it here.</li>
        </ol>
        <form method="post" action="<?= url('/account/branch-students/import') ?>" enctype="multipart/form-data" class="space-y-3">
          <?= csrfField() ?>
          <label class="block text-[11px] font-bold uppercase text-neutral-600">Everyone in the file is a<select name="role" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><option value="student">Student</option><option value="trainer">Tutor (teaches courses)</option></select></label>
          <?php if (count($branches) > 1): ?>
            <label class="block text-[11px] font-bold uppercase text-neutral-600">Branch for everyone in the file<select name="branch_id" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case"><?php foreach ($branches as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['title']) ?></option><?php endforeach; ?></select></label>
          <?php endif; ?>
          <input type="file" name="csv_file" accept=".csv,text/csv" required class="block w-full text-sm">
          <button type="submit" class="btn-primary">Upload</button>
        </form>
      </section>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
