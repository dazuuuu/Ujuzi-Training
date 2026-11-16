<?php
/** Requires $log, $lines, $size, $path in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-4">
  <section class="rounded-lg border border-neutral-200 bg-white p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-sm font-black uppercase tracking-wide text-neutral-900">Error log</h2>
        <p class="text-xs text-neutral-500">The real cause behind "Something went wrong" — newest at the bottom. <?= e(number_format($size / 1024, 1)) ?> KB · <?= e($path) ?></p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <form method="get" action="<?= url('/admin/error-logs') ?>" class="flex items-center gap-2">
          <select name="lines" class="input" onchange="this.form.submit()">
            <?php foreach ([100, 400, 1000, 5000] as $n): ?>
              <option value="<?= $n ?>" <?= $n === $lines ? 'selected' : '' ?>>Last <?= $n ?> lines</option>
            <?php endforeach; ?>
          </select>
        </form>
        <a href="<?= url('/admin/error-logs?lines=' . $lines) ?>" class="btn-secondary">Refresh</a>
        <a href="<?= url('/admin/error-logs?check=1') ?>" class="btn-primary">Check what's wrong</a>
        <form method="post" action="<?= url('/admin/error-logs/clear') ?>" data-confirm="Clear the error log?">
          <?= csrfField() ?>
          <button type="submit" class="btn-danger">Clear</button>
        </form>
      </div>
    </div>
  </section>

  <?php if (!empty($checks)): ?>
    <section class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
      <table class="w-full text-left text-xs">
        <thead class="bg-neutral-50 text-[10px] uppercase tracking-wide text-neutral-500"><tr><th class="p-3">Check</th><th class="p-3">Result</th><th class="p-3">Details</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $c): $col = ['ok' => '#166534', 'warn' => '#b45309', 'fail' => '#b91c1c'][$c['status']]; ?>
          <tr class="border-t border-neutral-100"><td class="p-3 font-bold"><?= e($c['name']) ?></td><td class="p-3 font-black uppercase" style="color:<?= $col ?>"><?= e($c['status']) ?></td><td class="p-3 text-neutral-700"><?= e($c['detail']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>
  <?php if (!empty($recent)): ?>
    <section class="rounded-lg border border-neutral-200 bg-white p-4">
      <h3 class="text-sm font-black uppercase tracking-wide text-neutral-900">Different errors seen (newest first)</h3>
      <ul class="mt-2 space-y-2 text-xs">
        <?php foreach (array_slice($recent, 0, 15) as $r): ?>
          <li class="rounded border border-neutral-100 p-2"><b><?= e($r['message']) ?></b><br><span class="text-neutral-500">×<?= (int) $r['count'] ?> · last <?= e($r['last']) ?></span><?= $r['hint'] !== '' ? '<br><span style="color:#166534;font-weight:700">What to do: ' . e($r['hint']) . '</span>' : '' ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if (trim($log) === ''): ?>
    <p class="rounded-lg border border-neutral-200 bg-white p-6 text-sm text-neutral-500">No errors logged.</p>
  <?php else: ?>
    <pre id="error-log" class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-words rounded-lg bg-neutral-900 p-4 text-xs leading-relaxed text-neutral-100"><?= e($log) ?></pre>
    <script>var l = document.getElementById('error-log'); l.scrollTop = l.scrollHeight;</script>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
