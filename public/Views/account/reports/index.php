<?php
/** Visual reports for an organisation or attachment provider. Requires $reportCharts, $from and $to. */
require __DIR__ . '/../layout-header.php';
$qs = http_build_query(array_filter(['from' => $from, 'to' => $to]));
?>
<div class="space-y-5">
  <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Reports</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold">Reports</h1>
      <p class="mt-1 text-sm font-medium text-neutral-600">Your numbers at a glance. Hover any bar or slice for its exact value, or download them all as Excel.</p>
    </div>
    <div class="flex flex-wrap items-end gap-2">
      <form method="get" action="<?= url('/account/reports') ?>" class="flex flex-wrap items-end gap-2">
        <label class="text-[11px] font-bold uppercase text-neutral-600">From<input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
        <label class="text-[11px] font-bold uppercase text-neutral-600">To<input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
        <button type="submit" class="btn-secondary">Show</button>
      </form>
      <a href="<?= e(url('/account/reports/export') . ($qs !== '' ? '?' . $qs : '')) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Excel</a>
    </div>
  </section>
  <?php require __DIR__ . '/../../partials/report-charts.php'; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
