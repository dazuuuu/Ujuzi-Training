<?php
/** Requires $filters, $report (title, subtitle, headers, rows, totals, summary, statusColumn, financeColumn), $preview, $organisations, $branches. */
require __DIR__ . '/../layout-header.php';
use App\Services\ActivityReport;

$qs = http_build_query(array_filter($filters, static fn($v) => $v !== '' && $v !== 0));
$total = count($report['rows']);
$pill = static function (string $text): string {
    $tone = match ($text) {
        'Completed', 'Recommended', 'Paid' => 'is-good',
        'In progress', 'Accepted', 'Ongoing', 'Paused', 'Partly paid' => 'is-mid',
        'Not paid', 'Rejected' => 'is-bad',
        default => '',
    };
    return '<span class="pill ' . $tone . '">' . e($text) . '</span>';
};
?>
<style>
  .rf label{display:flex;flex-direction:column;gap:2px;font-size:10px;font-weight:800;text-transform:uppercase;color:#6b7280;}
  .rf select,.rf input{border:1px solid #d1d5db;border-radius:6px;padding:5px 7px;font-size:13px;font-weight:500;text-transform:none;color:#111;background:#fff;min-width:0;}
  .pill{display:inline-block;border-radius:99px;padding:1px 7px;font-size:11px;font-weight:800;white-space:nowrap;background:#f3f4f6;color:#374151;}
  .pill.is-good{background:#e7f4ec;color:#0b6b3a;} .pill.is-mid{background:#fff4dc;color:#8a5a00;} .pill.is-bad{background:#fdecec;color:#a11;}
  .kpi{border:1px solid var(--ke-line,#e5e7eb);border-radius:8px;padding:6px 10px;background:#fff;}
  .kpi b{display:block;font-size:15px;} .kpi span{font-size:10px;font-weight:700;text-transform:uppercase;color:#6b7280;}
</style>

<div class="space-y-3">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <a href="<?= url('/admin/reports') ?>" class="text-xs font-bold text-neutral-500 hover:underline">← All reports</a>
    <div class="flex gap-2">
      <a href="<?= e(url('/admin/reports/activity/pdf') . ($qs !== '' ? '?' . $qs : '')) ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-1.5"><?= icon('file', 'h-4 w-4') ?> PDF</a>
      <a href="<?= e(url('/admin/reports/activity/xlsx') . ($qs !== '' ? '?' . $qs : '')) ?>" class="btn-primary inline-flex items-center gap-1.5"><?= icon('download', 'h-4 w-4') ?> Excel</a>
    </div>
  </div>

  <form method="get" action="<?= url('/admin/reports/activity') ?>" class="rf grid grid-cols-2 gap-2 rounded-lg border border-neutral-200 bg-white p-3 sm:grid-cols-4 lg:grid-cols-8 lg:items-end">
    <label class="col-span-2">Kind of organisation
      <select name="type" onchange="this.form.org.value='';this.form.branch.value='';this.form.status.value='';this.form.submit()">
        <?php foreach (ActivityReport::TYPES as $key => $label): ?><option value="<?= $key ?>" <?= $key === $filters['type'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="col-span-2">Organisation
      <select name="org" onchange="this.form.branch.value='';this.form.submit()">
        <option value="">All organisations</option>
        <?php foreach ($organisations as $id => $name): ?><option value="<?= (int) $id ?>" <?= (int) $id === $filters['org'] ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Branch
      <select name="branch" <?= $branches ? '' : 'disabled title="Pick an organisation first"' ?>>
        <option value="">All branches</option>
        <?php foreach ($branches as $b): ?><option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $filters['branch'] ? 'selected' : '' ?>><?= e($b['title']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Status
      <select name="status">
        <?php foreach (ActivityReport::STATUSES[$filters['type']] as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === $filters['status'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Finance
      <select name="finance">
        <?php foreach (ActivityReport::FINANCE as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === $filters['finance'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>From<input type="date" name="from" value="<?= e($filters['from']) ?>"></label>
    <label>To<input type="date" name="to" value="<?= e($filters['to']) ?>"></label>
    <div class="col-span-2 flex items-center gap-2 sm:col-span-1"><button type="submit" class="btn-secondary w-full">Show</button></div>
  </form>

  <p class="text-xs text-neutral-500"><?= e($report['subtitle']) ?> — dates filter when the student <?= $filters['type'] === 'attachment' ? 'requested attachment' : 'enrolled' ?>.</p>

  <div class="grid grid-cols-3 gap-2 sm:grid-cols-6 lg:grid-cols-11">
    <?php foreach ($report['summary'] as $label => $value): ?>
      <div class="kpi"><b><?= is_float($value) || str_contains($label, 'Ksh') ? number_format((float) $value) : (int) $value ?></b><span><?= e($label) ?></span></div>
    <?php endforeach; ?>
  </div>

  <?php if (!$total): ?>
    <p class="rounded-lg border border-dashed p-4 text-sm text-neutral-500" style="border-color:var(--ke-line)">Nothing matches these filters.</p>
  <?php else: ?>
    <?php if ($total > count($preview)): ?><p class="text-xs font-bold text-neutral-500">Showing the first <?= count($preview) ?> of <?= $total ?> — downloads include all.</p><?php endif; ?>
    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
      <table class="excel-table admin-data-table text-xs">
        <thead><tr><?php foreach ($report['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($preview as $row): ?>
            <tr><?php foreach ($row as $i => $cell): ?>
              <td class="<?= $i === 1 ? 'font-bold' : '' ?> whitespace-nowrap"><?= $i === $report['statusColumn'] || $i === $report['financeColumn'] ? $pill((string) $cell) : e(is_float($cell) ? number_format($cell, 2) : (string) $cell) ?></td>
            <?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><?php foreach ($report['totals'] as $cell): ?><th><?= e(is_float($cell) || is_int($cell) ? number_format((float) $cell, 2) : (string) $cell) ?></th><?php endforeach; ?></tr></tfoot>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
