<?php
/** Requires $view (charts | table), $reportCharts, $sections, $section, $report (title, headers, rows, totals), $preview, $from and $to. */
require __DIR__ . '/../layout-header.php';

$query = static fn(array $extra = []): string => ($qs = http_build_query(array_filter(array_merge(['from' => $from, 'to' => $to], $extra)))) !== '' ? '?' . $qs : '';
$total = count($report['rows']);
?>
<div class="space-y-5">
  <section>
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Reports</p>
    <h1 class="mt-2 font-serif-heading text-3xl font-bold">Reports</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">Pick a report, narrow it by dates if you like, then download it as Excel or PDF.</p>
  </section>

  <nav class="course-tabs" aria-label="Reports">
    <a href="<?= url('/admin/reports') . e($query(['view' => 'charts'])) ?>" role="tab" aria-selected="<?= $view === 'charts' ? 'true' : 'false' ?>" style="text-decoration:none">📊 Charts</a>
    <?php foreach ($sections as $key => [$title]): ?>
      <a href="<?= url('/admin/reports') . e($query(['section' => $key])) ?>" role="tab" aria-selected="<?= $view !== 'charts' && $key === $section ? 'true' : 'false' ?>" style="text-decoration:none"><?= e($title) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($view === 'charts'): ?>
    <form method="get" action="<?= url('/admin/reports') ?>" class="flex flex-wrap items-end gap-2">
      <input type="hidden" name="view" value="charts">
      <label class="text-[11px] font-bold uppercase text-neutral-600">From<input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
      <label class="text-[11px] font-bold uppercase text-neutral-600">To<input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
      <button type="submit" class="btn-secondary">Show</button>
      <span class="self-center text-xs font-semibold text-neutral-500">Pick any report tab above for its Excel / PDF table.</span>
    </form>
    <?php require __DIR__ . '/../../partials/report-charts.php'; ?>
  <?php else: ?>
  <section class="learn-card space-y-4 p-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h2 class="text-xl font-black text-gray-900"><?= e($report['title']) ?></h2>
        <p class="text-sm font-medium text-neutral-600"><?= e($sections[$section][1]) ?></p>
        <p class="mt-1 text-xs font-bold text-neutral-500"><?= $total ?> record<?= $total === 1 ? '' : 's' ?><?= $total > count($preview) ? ' · showing the first ' . count($preview) . ' — downloads include all' : '' ?></p>
      </div>
      <div class="flex flex-wrap items-end gap-2">
        <form method="get" action="<?= url('/admin/reports') ?>" class="flex flex-wrap items-end gap-2">
          <input type="hidden" name="section" value="<?= e($section) ?>">
          <label class="text-[11px] font-bold uppercase text-neutral-600">From<input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
          <label class="text-[11px] font-bold uppercase text-neutral-600">To<input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
          <button type="submit" class="btn-secondary">Show</button>
        </form>
        <a href="<?= url('/admin/reports/' . $section . '/xlsx') . e($query()) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Excel</a>
        <a href="<?= url('/admin/reports/' . $section . '/pdf') . e($query()) ?>" class="btn-secondary inline-flex items-center gap-2"><?= icon('file', 'h-4 w-4') ?> PDF</a>
      </div>
    </div>

    <div class="overflow-x-auto rounded-lg border" style="border-color:var(--ke-line)">
      <table class="w-full text-left text-sm">
        <thead class="bg-neutral-50 text-[11px] font-black uppercase text-neutral-600">
          <tr><?php foreach ($report['headers'] as $header): ?><th class="whitespace-nowrap px-3 py-2"><?= e($header) ?></th><?php endforeach; ?></tr>
        </thead>
        <tbody class="divide-y">
          <?php if (!$preview): ?>
            <tr><td colspan="<?= count($report['headers']) ?>" class="px-3 py-6 text-center font-semibold text-neutral-500">No records for this report.</td></tr>
          <?php endif; ?>
          <?php foreach ($preview as $row): ?>
            <tr>
              <?php foreach ($row as $cell): ?>
                <td class="max-w-xs truncate whitespace-nowrap px-3 py-1.5 font-medium text-gray-800" title="<?= e(is_float($cell) ? number_format($cell, 2) : (string) $cell) ?>"><?= e(is_float($cell) ? number_format($cell, 2) : (string) $cell) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <?php if ($report['totals'] && $preview): ?>
          <tfoot class="border-t-2 bg-neutral-50 font-black">
            <tr><?php foreach ($report['totals'] as $cell): ?><td class="whitespace-nowrap px-3 py-2"><?= e(is_float($cell) ? number_format($cell, 2) : (string) $cell) ?></td><?php endforeach; ?></tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
