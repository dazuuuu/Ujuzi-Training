<?php
/** Every certificate in the system, as a spreadsheet. Requires $certTab, $filters, $earned, $generated, $organisations, $courses, $batchLimit. */
require __DIR__ . '/../layout-header.php';
$qs = static fn(array $extra = []): string => http_build_query(array_filter($filters + ['tab' => $certTab] + $extra) + $extra);
$rows = $certTab === 'earned' ? $earned : $generated;
$tabs = '<a href="' . url('/admin/certificates') . '" class="' . ($certTab === 'earned' ? 'is-active' : '') . '">Earned by students</a>'
    . '<a href="' . url('/admin/certificates?tab=generated') . '" class="' . ($certTab === 'generated' ? 'is-active' : '') . '">Generated here</a>'
    . '<span class="xl-count">' . count($rows) . ' record' . (count($rows) === 1 ? '' : 's') . '</span>';
?>
<div class="space-y-3">
  <section class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Documents</p>
      <h1 class="mt-1 font-serif-heading text-2xl font-bold">Certificates</h1>
    </div>
    <a href="<?= url('/admin/certificates/generate') ?>" class="btn-primary">+ Generate certificates</a>
  </section>

  <div>
    <form method="get" action="<?= url('/admin/certificates') ?>" class="xl-toolbar">
      <input type="hidden" name="tab" value="<?= e($certTab) ?>">
      <span><label for="f-q">Find</label><input type="search" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, reg. no., course…"></span>
      <span><label for="f-org">Organisation</label>
        <select id="f-org" name="organisation_id"><option value="">(All)</option>
          <?php foreach ($organisations as $o): ?><option value="<?= (int) $o['id'] ?>" <?= (int) $o['id'] === $filters['organisation_id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
        </select></span>
      <?php if ($certTab === 'earned'): ?>
        <span><label for="f-course">Course</label>
          <select id="f-course" name="course_id"><option value="">(All)</option>
            <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $filters['course_id'] ? 'selected' : '' ?>><?= e($c['title'] . ' — ' . $c['organisation_name']) ?></option><?php endforeach; ?>
          </select></span>
      <?php endif; ?>
      <span><label for="f-from"><?= $certTab === 'earned' ? 'Completed' : 'Issued' ?></label><input type="date" id="f-from" name="from" value="<?= e($filters['from']) ?>"><label for="f-to">to</label><input type="date" id="f-to" name="to" value="<?= e($filters['to']) ?>"></span>
      <span><button type="submit">Filter</button><a class="xl-btn" href="<?= url('/admin/certificates' . ($certTab === 'generated' ? '?tab=generated' : '')) ?>">Clear</a></span>
      <span class="xl-grow"></span>
      <span>
        <button type="submit" form="cert-pick" id="cert-selected" disabled>Download selected (<b>0</b>)</button>
        <a class="xl-btn is-green" target="_blank" href="<?= url('/admin/certificates/download?' . $qs()) ?>">Download all<?= count($rows) > $batchLimit ? ' (first ' . $batchLimit . ')' : '' ?></a>
        <a class="xl-btn" href="<?= url('/admin/certificates?' . $qs(['export' => 'xlsx'])) ?>">Excel</a>
      </span>
    </form>

    <form method="get" action="<?= url('/admin/certificates/download') ?>" target="_blank" id="cert-pick">
      <input type="hidden" name="tab" value="<?= e($certTab) ?>">
      <table data-xl data-xl-tabs="<?= e($tabs) ?>">
        <?php if ($certTab === 'earned'): ?>
          <thead><tr><th><input type="checkbox" id="cert-all" aria-label="Select all"></th><th>Name</th><th>Reg. No.</th><th>Phone</th><th>Email</th><th>Course</th><th>Organisation</th><th>Completed</th><th>Certificate No.</th><th>Certificate</th></tr></thead>
          <tbody>
            <?php foreach ($earned as $r): $pair = $r['user_id'] . '-' . $r['course_id']; ?>
              <tr>
                <td><input type="checkbox" name="pairs[]" value="<?= e($pair) ?>" class="cert-box" aria-label="Select <?= e($r['name']) ?>"></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['registration_number']) ?></td>
                <td><?= e($r['phone']) ?></td>
                <td><?= e($r['email']) ?></td>
                <td><?= e($r['course_title']) ?></td>
                <td><?= e($r['organisation_name']) ?></td>
                <td><?= e($r['completed_at']) ?></td>
                <td><?= e($r['certificate_no'] ?? '') ?></td>
                <td><a target="_blank" href="<?= url('/admin/certificates/download?tab=earned&pairs[]=' . $pair) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$earned): ?><tr><td colspan="9">No certificates match.</td></tr><?php endif; ?>
          </tbody>
        <?php else: ?>
          <thead><tr><th><input type="checkbox" id="cert-all" aria-label="Select all"></th><th>Certificate No.</th><th>Name</th><th>ID / Reg. No.</th><th>Skills</th><th>Design</th><th>Issued</th><th>Certificate</th></tr></thead>
          <tbody>
            <?php foreach ($generated as $r): ?>
              <tr>
                <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="cert-box" aria-label="Select <?= e($r['recipient_name']) ?>"></td>
                <td><?= e($r['certificate_no']) ?></td>
                <td><?= e($r['recipient_name']) ?></td>
                <td><?= e((string) $r['id_number']) ?></td>
                <td title="<?= e($r['skills']) ?>"><?= e($r['skills']) ?></td>
                <td><?= e($r['organisation_name'] ?: 'Platform') ?></td>
                <td><?= e($r['issued_on']) ?></td>
                <td><a target="_blank" href="<?= url('/admin/certificates/download?tab=generated&ids[]=' . (int) $r['id']) ?>">Open</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$generated): ?><tr><td colspan="8">No generated certificates match.</td></tr><?php endif; ?>
          </tbody>
        <?php endif; ?>
      </table>
    </form>
  </div>
</div>
<script src="<?= asset('assets/js/excel-grid.js') ?>"></script>
<script>
(function () {
  var all = document.getElementById('cert-all'), btn = document.getElementById('cert-selected');
  function sync() { var n = document.querySelectorAll('.cert-box:checked').length; btn.querySelector('b').textContent = n; btn.disabled = n === 0; }
  document.addEventListener('change', function (e) { if (e.target.classList.contains('cert-box')) sync(); });
  if (all) all.addEventListener('change', function () { document.querySelectorAll('.cert-box').forEach(function (b) { b.checked = all.checked; }); sync(); });
})();
</script>
<?php require __DIR__ . '/../layout-footer.php'; ?>
