<?php
/**
 * Completed attachments for processing payments: filters, totals per branch,
 * the list, and Excel / PDF (view and print) buttons. Requires $rows,
 * $perBranch, $branches, $branchId, $from, $to, $baseUrl; optional
 * $providers (id => name) and $providerId for Super Admin.
 */
$providers = $providers ?? null;
$providerId = (int) ($providerId ?? 0);
$state = $state ?? 'completed';
$stateLabel = \App\Services\AttachmentReport::PAYMENT_STATES[$state] ?? 'Completed';
$qs = http_build_query(array_filter(['provider' => $providerId ?: null, 'branch' => $branchId ?: null, 'state' => $state !== 'completed' ? $state : null, 'from' => $from, 'to' => $to]));
$suffix = $qs !== '' ? '?' . $qs : '';
?>
<style>
  .status-pill{display:inline-block;border-radius:99px;padding:1px 8px;font-size:11px;font-weight:800;}
  .status-pill.is-done{background:#e7f4ec;color:#0b6b3a;}
  .status-pill.is-live{background:#fff4dc;color:#8a5a00;}
</style>
<div class="space-y-4">
  <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Attachments</p>
      <h1 class="mt-1 font-serif-heading text-2xl font-bold"><?= e($stateLabel) ?> attachments</h1>
      <p class="mt-1 text-sm text-neutral-600">Per branch, with each student's status — download or print to process payments.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="<?= e(url($baseUrl . '/pdf') . $suffix) ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2"><?= icon('file', 'h-4 w-4') ?> View / print PDF</a>
      <a href="<?= e(url($baseUrl . '/excel') . $suffix) ?>" class="btn-primary inline-flex items-center gap-2"><?= icon('download', 'h-4 w-4') ?> Excel</a>
    </div>
  </section>

  <form method="get" action="<?= url($baseUrl) ?>" class="flex flex-wrap items-end gap-2">
    <?php if ($providers !== null): ?>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Organisation providing attachment
        <select name="provider" onchange="this.form.branch && (this.form.branch.value = ''); this.form.submit()" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm normal-case">
          <option value="">All</option>
          <?php foreach ($providers as $id => $name): ?><option value="<?= (int) $id ?>" <?= (int) $id === $providerId ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <?php if (count($branches) > 1 || ($providers !== null && $branches)): ?>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Branch
        <select name="branch" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm normal-case">
          <option value="">All branches</option>
          <?php foreach ($branches as $b): ?><option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === (int) $branchId ? 'selected' : '' ?>><?= e($b['title']) ?></option><?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <label class="text-[11px] font-bold uppercase text-neutral-600">Status
      <select name="state" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm normal-case">
        <?php foreach (\App\Services\AttachmentReport::PAYMENT_STATES as $key => $label): ?><option value="<?= e($key) ?>" <?= $key === $state ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="text-[11px] font-bold uppercase text-neutral-600"><?= $state === 'ongoing' ? 'Started' : ($state === 'all' ? 'Active' : 'Completed') ?> from<input type="date" name="from" value="<?= e($from) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
    <label class="text-[11px] font-bold uppercase text-neutral-600">to<input type="date" name="to" value="<?= e($to) ?>" class="mt-1 block rounded-lg border border-neutral-300 p-2 text-sm"></label>
    <button type="submit" class="btn-secondary">Show</button>
    <?php if ($qs !== ''): ?><a href="<?= url($baseUrl) ?>" class="self-center text-xs font-bold underline">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <p class="rounded-xl border border-dashed p-6 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No <?= e(strtolower($stateLabel)) ?> attachments for these filters.</p>
  <?php else: ?>
    <div class="flex flex-wrap gap-2">
      <span class="rounded-full border px-3 py-1.5 text-xs font-black" style="border-color:var(--ke-green);color:var(--ke-green)">Total · <?= count($rows) ?></span>
      <?php foreach ($perBranch as $branch => $count): ?>
        <span class="rounded-full border px-3 py-1.5 text-xs font-bold" style="border-color:var(--ke-line)"><?= e($branch) ?> · <?= (int) $count ?></span>
      <?php endforeach; ?>
    </div>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead><tr><?php foreach (\App\Services\AttachmentReport::PAYMENT_HEADERS as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $i => $cell): ?><td class="<?= $i === 1 ? 'font-black' : '' ?>"><?php if ($i === 8): ?><span class="status-pill <?= $cell === 'Completed' ? 'is-done' : 'is-live' ?>"><?= e((string) $cell) ?></span><?php else: ?><?= e((string) $cell) ?><?php endif; ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
