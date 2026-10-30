<?php
/** Requires $branches, $activeApplications and $completedApplications (each with 'fees') in scope. */
require __DIR__ . '/../layout-header.php';

$activeApplications = $activeApplications ?? [];
$from = $from ?? '';
$to = $to ?? '';
$dateQs = http_build_query(array_filter(['from' => $from, 'to' => $to]));
$decidable = array_filter($activeApplications, static fn(array $a): bool => in_array($a['status'], ['pending', 'paused'], true));
$unreadNotes = $unreadNotes ?? [];
$completedApplications = $completedApplications ?? [];
$hasAny = $activeApplications || $completedApplications;

$studentName = static fn(array $a): string => trim((string) ($a['first_name'] ?? '') . ' ' . (string) ($a['last_name'] ?? '')) ?: (string) ($a['email'] ?? 'Student');
$when = static fn(?string $at): string => $at ? date('j M Y', strtotime($at)) : '—';
/** Text the search box matches a row against. */
$searchText = static fn(array $a): string => strtolower(implode(' ', [
    $studentName($a), $a['email'] ?? '', $a['phone'] ?? '', $a['category_name'] ?? '', $a['branch_title'] ?? '', $a['status'] ?? '',
    $a['course_title'] ?? '', $a['course_organisation_name'] ?? '', $a['student_branch_title'] ?? '', $a['registration_number'] ?? '',
]));

?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Branch admin</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachees</h1>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-600">
        Accept attachees for your branch<?= count($branches) > 1 ? 'es' : '' ?>, then mark their attachment complete once it's finished.
        Open a request to send the student a note, put it on hold, decline it, or resend their recommendation letter.
        Each student's fees and course progress are shown before you accept or complete them. A warning appears when a student has paid less than <?= \App\Services\WalletService::minPaymentPercent() ?>% of their fees.
        A student can only be marked completed once their balance is Ksh 0.
      </p>
    </div>
  </section>

  <?php if (!$branches): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      No branch has been assigned to you yet. Ask the attachment provider to assign you as a branch admin.
    </section>
  <?php else: ?>
    <form method="get" action="<?= url('/account/branch-admin') ?>" class="flex flex-wrap items-end gap-2 rounded-xl border border-neutral-200 bg-white p-3 shadow-sm">
      <label class="text-xs font-bold uppercase text-neutral-600">Requested from
        <input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm">
      </label>
      <label class="text-xs font-bold uppercase text-neutral-600">to
        <input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm">
      </label>
      <button type="submit" class="btn-secondary">Show</button>
      <?php if ($dateQs !== ''): ?><a href="<?= url('/account/branch-admin') ?>" class="self-center text-xs font-bold underline">All dates</a><?php endif; ?>
      <a href="<?= url('/account/attachment-payments') ?>" class="btn-secondary ml-auto inline-flex items-center gap-2"><?= icon('file', 'h-4 w-4') ?> Completed list for payments</a>
      <a href="<?= e(url('/account/branch-admin/export') . ($dateQs !== '' ? '?' . $dateQs : '')) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Download Excel</a>
    </form>
  <?php endif; ?>

  <?php if ($branches && !$hasAny): ?>
    <section class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      <?= $dateQs !== '' ? 'No attachees requested on these dates.' : 'No attachees for your branch' . (count($branches) > 1 ? 'es' : '') . ' yet.' ?>
    </section>
  <?php elseif ($branches): ?>
    <div>
      <label for="attachee-search" class="sr-only">Search attachees</label>
      <input type="search" id="attachee-search" placeholder="Search by name, email, phone, category or branch…" class="w-full sm:max-w-md rounded-lg border border-neutral-300 p-2 text-sm">
    </div>

    <section class="space-y-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-serif-heading text-lg font-bold">Requests &amp; current attachees <span class="text-sm font-bold text-neutral-500">(<?= count($activeApplications) ?>)</span></h2>
        <?php if ($decidable): ?>
          <form method="post" action="<?= url('/account/branch-admin/bulk-accept') ?>" id="bulk-accept-form" onsubmit="return this.querySelector('[data-count]').dataset.count !== '0' && confirm('Accept the selected students?');">
            <?= csrfField() ?>
            <button type="submit" class="btn-primary" id="bulk-accept-button" disabled>Accept selected (<span data-count="0">0</span>)</button>
          </form>
        <?php endif; ?>
      </div>
      <?php if ($decidable): ?>
        <label class="inline-flex items-center gap-2 text-xs font-bold text-neutral-600"><input type="checkbox" id="bulk-select-all"> Select every student waiting for a decision</label>
      <?php endif; ?>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-sheet w-full">
          <thead><tr><th style="width:1.5rem"></th><th>#</th><th>Student</th><th>Reg. No.</th><th>Phone</th><th>Course</th><th>Category</th><th>Branch</th><th>Requested</th><th>Fees (this course)</th><th>Progress</th><th>Assessment</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody class="searchable-list">
            <?php foreach ($activeApplications as $n => $application): $row = $application; require __DIR__ . '/row.php'; endforeach; ?>
            <tr class="no-results" <?= $activeApplications ? 'hidden' : '' ?>><td colspan="14" class="text-center font-bold text-neutral-500"><?= $activeApplications ? 'No match.' : 'No open requests.' ?></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="space-y-3">
      <h2 class="font-serif-heading text-lg font-bold">Completed attachees <span class="text-sm font-bold text-neutral-500">(<?= count($completedApplications) ?>)</span></h2>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-sheet w-full">
          <thead><tr><th style="width:1.5rem"></th><th>#</th><th>Student</th><th>Reg. No.</th><th>Phone</th><th>Course</th><th>Category</th><th>Branch</th><th>Completed</th><th>Fees (this course)</th><th>Progress</th><th>Assessment</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody class="searchable-list">
            <?php foreach ($completedApplications as $n => $application): $row = $application; $completedRow = true; require __DIR__ . '/row.php'; $completedRow = false; endforeach; ?>
            <tr class="no-results" <?= $completedApplications ? 'hidden' : '' ?>><td colspan="14" class="text-center font-bold text-neutral-500"><?= $completedApplications ? 'No match.' : 'No completed attachees yet.' ?></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <script>
    (function () {
      // Bulk accept: keep the count and the select-all box in step with the ticks.
      var picks = function () { return Array.prototype.slice.call(document.querySelectorAll('.bulk-pick')).filter(function (b) { return !b.closest('[data-search]').hidden; }); };
      var all = document.getElementById('bulk-select-all');
      var button = document.getElementById('bulk-accept-button');
      function sync() {
        if (!button) return;
        var n = document.querySelectorAll('.bulk-pick:checked').length;
        button.querySelector('[data-count]').textContent = n;
        button.querySelector('[data-count]').dataset.count = String(n);
        button.disabled = n === 0;
        if (all) { var list = picks(); all.checked = list.length > 0 && list.every(function (b) { return b.checked; }); }
      }
      document.addEventListener('change', function (e) { if (e.target.classList.contains('bulk-pick')) sync(); });
      if (all) all.addEventListener('change', function () { picks().forEach(function (b) { b.checked = all.checked; }); sync(); });

      var input = document.getElementById('attachee-search');
      input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('.searchable-list').forEach(function (body) {
          var rows = body.querySelectorAll('[data-search]');
          var shown = 0;
          rows.forEach(function (row) {
            var match = q === '' || row.dataset.search.indexOf(q) !== -1;
            row.hidden = !match;
            if (match) shown++;
          });
          var empty = body.querySelector('.no-results');
          if (empty && rows.length) {
            empty.hidden = shown > 0;
            (empty.querySelector('td') || empty).textContent = 'No match.';
          }
        });
      });
    })();
    </script>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
