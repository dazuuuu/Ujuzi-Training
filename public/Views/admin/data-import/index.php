<?php
/** No required variables beyond the layout's own. */
require __DIR__ . '/../layout-header.php';
$importErrors = $_SESSION['data_import_errors'] ?? [];
unset($_SESSION['data_import_errors']);
?>

<div class="max-w-3xl space-y-4">
  <section class="rounded-lg border border-neutral-200 bg-white p-4">
    <h2 class="text-base font-bold">Export</h2>
    <p class="text-xs text-neutral-500 mt-1">Downloads everything in this database right now — admins, users, courses, organisations, settings — as one .sql file. Keep it somewhere safe; it's the file to bring back with the import below if anything is ever lost.</p>
    <a href="<?= url('/admin/data-import/export') ?>" class="btn-secondary mt-3 inline-flex">Download a full backup (.sql)</a>
  </section>

  <section class="rounded-lg border bg-white p-4" style="border:2px solid var(--ke-green)">
    <h2 class="text-base font-bold">Import lost data</h2>
    <p class="text-xs text-neutral-500 mt-1">
      Upload an older backup (.sql, or a .zip containing one) to add back whatever it has that this database doesn't.
      This is <strong>not</strong> a full restore: nothing here is dropped, recreated, or overwritten. For every row in the
      file, if a row with that same id/key already exists — whether it's old data still here or a brand-new user created
      since the backup — it's left exactly as it is. Only rows this database is actually missing get added. Mail, payment
      and other live settings in the backup are ignored entirely, so this can't change how the site is configured right now.
    </p>
    <?php if ($importErrors): ?>
      <details class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800">
        <summary class="cursor-pointer font-bold">Show what failed to import (<?= count($importErrors) ?>)</summary>
        <ul class="mt-2 space-y-1 font-mono"><?php foreach ($importErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </details>
    <?php endif; ?>
    <form method="post" action="<?= url('/admin/data-import/import') ?>" enctype="multipart/form-data" class="mt-3 space-y-3" onsubmit="return confirm('Add back any rows this backup has that are missing here? Nothing already in this database will be changed or removed.');">
      <?= csrfField() ?>
      <div>
        <label class="block text-xs font-semibold text-neutral-600 mb-1">Backup file (.sql, or a .zip containing one)</label>
        <input type="file" name="backup" accept=".sql,.zip" required class="block w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
      </div>
      <button type="submit" class="btn-primary">Add back missing data</button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
