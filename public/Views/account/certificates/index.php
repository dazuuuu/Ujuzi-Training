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

  <section class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="font-serif-heading text-lg font-bold">Certificates earned <span class="text-sm font-bold text-neutral-500">(<?= count($earned) ?>)</span></h2>
      <?php if ($earned): ?><input type="search" id="cert-search" placeholder="Search name, reg. no. or course…" class="w-full sm:max-w-xs rounded-lg border border-neutral-300 p-2 text-sm"><?php endif; ?>
    </div>
    <?php if (!$earned): ?>
      <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No student has earned a certificate on your courses yet. They appear here once they finish a course (and its attachment, when it needs one).</p>
    <?php else: ?>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-table admin-data-table" id="cert-table">
          <thead><tr><th>Student</th><th>Reg. No.</th><th>Phone</th><th>Course</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($earned as $row): ?>
              <tr data-search="<?= e(strtolower($row['name'] . ' ' . $row['registration_number'] . ' ' . $row['course_title'])) ?>">
                <td class="font-black"><?= e($row['name']) ?></td>
                <td><?= e($row['registration_number']) ?></td>
                <td><?= e($row['phone'] ?: '—') ?></td>
                <td><?= e($row['course_title']) ?></td>
                <td><a href="<?= url('/account/certificates/' . (int) $row['user_id'] . '/' . (int) $row['course_id']) ?>" class="btn-primary" style="padding:0.25rem 0.6rem;font-size:0.72rem;">View / print</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <script>
        document.getElementById('cert-search').addEventListener('input', function () {
          var q = this.value.trim().toLowerCase();
          document.querySelectorAll('#cert-table tbody tr').forEach(function (tr) { tr.hidden = q !== '' && tr.dataset.search.indexOf(q) === -1; });
        });
      </script>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
