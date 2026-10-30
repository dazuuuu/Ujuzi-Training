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
        <form method="post" action="<?= url('/admin/error-logs/clear') ?>" onsubmit="return confirm('Clear the error log?')">
          <?= csrfField() ?>
          <button type="submit" class="btn-danger">Clear</button>
        </form>
      </div>
    </div>
  </section>

  <?php if (trim($log) === ''): ?>
    <p class="rounded-lg border border-neutral-200 bg-white p-6 text-sm text-neutral-500">No errors logged.</p>
  <?php else: ?>
    <pre id="error-log" class="max-h-[70vh] overflow-auto whitespace-pre-wrap break-words rounded-lg bg-neutral-900 p-4 text-xs leading-relaxed text-neutral-100"><?= e($log) ?></pre>
    <script>var l = document.getElementById('error-log'); l.scrollTop = l.scrollHeight;</script>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
