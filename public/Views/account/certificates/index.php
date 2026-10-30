<?php
/**
 * An organisation's certificates: its own design (upload + place the details)
 * and the certificates its students earned. Requires $template, $isPdf,
 * $layout, $usesPlatformDesign, $mode, $globalTemplate and $earned.
 */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Certificates</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Your certificates</h1>
    <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
      Choose the global certificate or your own organisational certificate. Every certificate for your courses — one per course — is generated on the design you choose.
      <?= $mode === 'organisation' && !$template ? 'Upload your design below; until then your students get the global certificate.' : '' ?>
    </p>
  </section>

  <form method="post" action="<?= url('/account/certificates/mode') ?>" class="space-y-3 rounded-xl border bg-white p-5 shadow-sm" style="border-color:var(--ke-line)">
    <?= csrfField() ?>
    <h2 class="font-serif-heading text-lg font-bold">Which certificate do your students get?</h2>
    <div class="grid gap-3 sm:grid-cols-2">
      <label class="branch-pick" style="align-items:flex-start">
        <input type="radio" name="mode" value="global" <?= $mode === 'global' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span><span class="block text-sm font-black text-gray-800">Global certificate</span>
          <span class="block text-xs font-semibold text-neutral-600">The <?= e(appName()) ?> certificate, the same for every organisation.<?= $globalTemplate ? '' : ' (Super Admin has not uploaded its design yet — the built-in design is used.)' ?></span></span>
      </label>
      <label class="branch-pick" style="align-items:flex-start">
        <input type="radio" name="mode" value="organisation" <?= $mode === 'organisation' ? 'checked' : '' ?> onchange="this.form.submit()">
        <span><span class="block text-sm font-black text-gray-800">Our organisational certificate</span>
          <span class="block text-xs font-semibold text-neutral-600">Your own design, with the student's name, course and the rest placed where you drag them.</span></span>
      </label>
    </div>
    <noscript><button type="submit" class="btn-secondary">Save choice</button></noscript>
  </form>

  <?php if ($mode === 'organisation'): ?>
  <?php
    $edType = 'certificate';
    $edTitle = 'Your certificate design';
    $edHelp = 'Upload a PDF or image of your certificate, then drag the student\'s name, course, organisation, registration number and date to where they go.';
    $edTemplate = $template;
    $edIsPdf = $isPdf;
    $edLayout = $layout;
    $edUploadUrl = url('/account/certificates/template');
    $edLayoutUrl = url('/account/certificates/layout');
    $edNote = null;
    require __DIR__ . '/../../partials/document-editor.php';
  ?>
  <?php endif; ?>

  <section class="space-y-2">
    <?php $courseNames = array_values(array_unique(array_column($earned, 'course_title'))); sort($courseNames); ?>
    <h2 class="font-serif-heading text-lg font-bold">Students with your certificates</h2>
    <?php if (!$earned): ?>
      <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No student has earned a certificate on your courses yet. They appear here once they finish a course (and its attachment, when it needs one).</p>
    <?php else: ?>
      <div>
        <div class="xl-toolbar">
          <span><label for="cert-search">Find</label><input type="search" id="cert-search" placeholder="Name, reg. no., phone…"></span>
          <span><label for="cert-course">Course</label><select id="cert-course"><option value="">(All)</option><?php foreach ($courseNames as $cn): ?><option value="<?= e(strtolower($cn)) ?>"><?= e($cn) ?></option><?php endforeach; ?></select></span>
          <span class="xl-grow"></span>
          <span>
            <button type="submit" form="cert-form" id="cert-selected" disabled>Download selected (<b>0</b>)</button>
            <button type="button" class="is-green" id="cert-shown">Download all shown (<b><?= count($earned) ?></b>)</button>
          </span>
        </div>
        <form method="get" action="<?= url('/account/certificates/download') ?>" target="_blank" id="cert-form">
          <table data-xl id="cert-table" data-xl-tabs="<?= e('<span class="is-active">Certificates</span><span class="xl-count" id="cert-count">' . count($earned) . ' records</span>') ?>">
            <thead><tr><th><input type="checkbox" id="cert-all" aria-label="Select all shown"></th><th>Student</th><th>Reg. No.</th><th>Phone</th><th>Course</th><th>Completed</th><th>Certificate No.</th><th>Certificate</th></tr></thead>
            <tbody>
              <?php foreach ($earned as $row): ?>
                <tr data-search="<?= e(strtolower($row['name'] . ' ' . $row['registration_number'] . ' ' . $row['phone'] . ' ' . ($row['certificate_no'] ?? ''))) ?>" data-course="<?= e(strtolower($row['course_title'])) ?>">
                  <td><input type="checkbox" name="pairs[]" value="<?= (int) $row['user_id'] . '-' . (int) $row['course_id'] ?>" class="cert-box" aria-label="Select <?= e($row['name']) ?>"></td>
                  <td><?= e($row['name']) ?></td>
                  <td><?= e($row['registration_number']) ?></td>
                  <td><?= e($row['phone']) ?></td>
                  <td><?= e($row['course_title']) ?></td>
                  <td><?= e($row['completed_at'] ?? '') ?></td>
                  <td><?= e($row['certificate_no'] ?? '') ?></td>
                  <td><a href="<?= url('/account/certificates/' . (int) $row['user_id'] . '/' . (int) $row['course_id']) ?>">Open</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </form>
      </div>
      <script src="<?= asset('assets/js/excel-grid.js') ?>"></script>
      <script>
      (function () {
        var form = document.getElementById('cert-form'), table = document.getElementById('cert-table'), search = document.getElementById('cert-search'), course = document.getElementById('cert-course'),
            sel = document.getElementById('cert-selected'), shown = document.getElementById('cert-shown'), all = document.getElementById('cert-all'), count = document.getElementById('cert-count');
        var rows = function () { return Array.prototype.slice.call(table.tBodies[0].rows); };
        function sync() {
          var visible = rows().filter(function (tr) { return !tr.hidden; }).length;
          var n = document.querySelectorAll('.cert-box:checked').length;
          sel.querySelector('b').textContent = n; sel.disabled = n === 0;
          shown.querySelector('b').textContent = visible; shown.disabled = visible === 0;
          if (count) count.textContent = visible + ' records';
        }
        function filter() {
          var q = search.value.trim().toLowerCase(), c = course.value;
          rows().forEach(function (tr) {
            tr.hidden = (q !== '' && tr.dataset.search.indexOf(q) === -1) || (c !== '' && tr.dataset.course !== c);
            if (tr.hidden) tr.querySelector('.cert-box').checked = false;
          });
          window.xlRenumber(table); sync();
        }
        search.addEventListener('input', filter); course.addEventListener('change', filter);
        document.addEventListener('change', function (e) { if (e.target.classList.contains('cert-box')) sync(); });
        all.addEventListener('change', function () { rows().forEach(function (tr) { if (!tr.hidden) tr.querySelector('.cert-box').checked = all.checked; }); sync(); });
        shown.addEventListener('click', function () { rows().forEach(function (tr) { tr.querySelector('.cert-box').checked = !tr.hidden; }); sync(); form.submit(); });
      })();
      </script>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
