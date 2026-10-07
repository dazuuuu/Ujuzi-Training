<?php
/** Requires $applications, $fees (student id => summary), $counts (status => n), $status, $query, $from, $to and $tabs. */
use App\Models\AttachmentApplication;

require __DIR__ . '/../layout-header.php';

$when = static fn(?string $at): string => $at ? date('j M Y', strtotime($at)) : '—';
$tabCount = static function (string $key) use ($counts): int {
    if ($key === '') {
        return array_sum($counts);
    }
    if ($key === AttachmentApplication::STATUS_COMPLETED) {
        return (int) ($counts['completed'] ?? 0) + (int) ($counts['recommended'] ?? 0);
    }
    return (int) ($counts[$key] ?? 0);
};
$filters = static fn(array $over = []): string => http_build_query(array_filter(array_merge(['status' => $status, 'q' => $query, 'from' => $from, 'to' => $to], $over)));
$link = static fn(string $s, string $q): string => url('/admin/attachments') . '?' . $filters(['status' => $s, 'q' => $q]);
$exportUrl = url('/admin/attachments/export') . (($qs = $filters()) !== '' ? '?' . $qs : '');
?>
<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachments</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Attachment requests</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">Every student who has sent a request to an organisation providing attachment, with where it stands.</p>
    </div>
    <a href="<?= e($exportUrl) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Download Excel</a>
  </section>

  <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
    <?php foreach ($tabs as $key => $label):
      $active = $key === $status;
    ?>
      <a href="<?= e($link($key, $query)) ?>" class="rounded-full border px-3 py-1.5 text-xs font-black uppercase" <?= $active ? 'aria-current="page"' : '' ?>
         style="border-color:<?= $active ? 'var(--ke-green)' : 'var(--ke-line)' ?>;background:<?= $active ? 'var(--ke-green)' : '#fff' ?>;color:<?= $active ? '#fff' : 'inherit' ?>">
        <?= e($label) ?> · <?= $tabCount($key) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="<?= url('/admin/attachments') ?>" class="flex flex-wrap items-end gap-2">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="flex-1 min-w-[14rem] text-xs font-bold uppercase text-neutral-600">Search
      <input type="search" name="q" value="<?= e($query) ?>" placeholder="Student, email, phone, provider, branch or category…" class="mt-1 w-full rounded-lg border border-neutral-300 p-2 text-sm normal-case">
    </label>
    <label class="text-xs font-bold uppercase text-neutral-600">Requested from
      <input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm">
    </label>
    <label class="text-xs font-bold uppercase text-neutral-600">to
      <input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm">
    </label>
    <button type="submit" class="btn-secondary">Apply</button>
    <?php if ($query !== '' || $from !== '' || $to !== ''): ?><a href="<?= e(url('/admin/attachments') . ($status !== '' ? '?status=' . urlencode($status) : '')) ?>" class="self-center text-xs font-bold underline">Clear</a><?php endif; ?>
  </form>
  <p class="text-xs font-semibold text-neutral-500">For one day, set both dates to that day. The Excel download uses the same filters.</p>

  <?php if (!$applications): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">
      <?= $query !== '' ? 'No attachment requests match your search.' : 'No attachment requests here yet.' ?>
    </p>
  <?php else: ?>
    <p class="text-xs font-bold text-neutral-600"><?= count($applications) ?> request<?= count($applications) === 1 ? '' : 's' ?> — the Excel download contains exactly these.</p>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead>
          <tr>
            <th>Student</th><th>Contact</th><th>Course category</th><th>Attachment provider</th><th>Branch</th>
            <th>Status</th><th>Requested</th><th>Accepted</th><th>Completed</th><th>Fees paid</th><th>Balance</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $a):
            $f = $fees[(int) $a['student_user_id']] ?? null;
            $st = (string) $a['status'];
          ?>
            <tr>
              <td class="font-black"><?= e(trim($a['first_name'] . ' ' . $a['last_name']) ?: $a['email']) ?><?php if (!empty($a['registration_number'])): ?><span class="block text-[10px] font-bold text-neutral-500"><?= e($a['registration_number']) ?></span><?php endif; ?></td>
              <td><?= e($a['email']) ?><?= !empty($a['phone']) ? '<br><span class="text-neutral-500">' . e($a['phone']) . '</span>' : '' ?></td>
              <td><?= e($a['category_name'] ?? '—') ?><?= !empty($a['category_organisation_name']) ? '<br><span class="text-neutral-500">' . e($a['category_organisation_name']) . '</span>' : '' ?></td>
              <td><?= e($a['provider_organisation_name'] ?: trim($a['provider_first_name'] . ' ' . $a['provider_last_name'])) ?></td>
              <td><?= e($a['branch_title'] ?? '—') ?></td>
              <td><span class="text-[11px] font-black uppercase" style="color:<?= in_array($st, ['pending', 'paused', 'rejected'], true) ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= e(AttachmentApplication::statusLabel($st)) ?></span></td>
              <td><?= e($when($a['selected_at'] ?? null)) ?></td>
              <td><?= e($when($a['accepted_at'] ?? null)) ?></td>
              <td><?= e($when($a['recommended_at'] ?? ($a['completed_at'] ?? null))) ?></td>
              <td><?= $f && $f['courses'] ? 'Ksh ' . number_format($f['paid'], 2) . ' <span class="text-neutral-500">(' . (int) floor($f['paid_ratio'] * 100) . '%)</span>' : '—' ?></td>
              <td style="color:<?= $f && !$f['is_settled'] ? 'var(--ke-red)' : 'inherit' ?>"><?= $f && $f['courses'] ? 'Ksh ' . number_format($f['balance'], 2) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
