<?php
/** Requires $pendingMigrations, $migrationStatus in scope. */
require __DIR__ . '/../layout-header.php';
$pendingMigrations = $pendingMigrations ?? [];
$migrationStatus = $migrationStatus ?? [];
$appliedCount = count(array_filter($migrationStatus, fn(array $row): bool => !empty($row['applied'])));
?>

<div class="max-w-4xl bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
  <div>
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">System Updates</h2>
    <p class="text-sm text-neutral-500 mt-1">Review and run database migrations from the admin panel.</p>
  </div>

  <?php if ($pendingMigrations): ?>
    <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-4">
      <p class="text-xs font-bold uppercase tracking-widest text-neutral-600 mb-3">Pending migrations</p>
      <ul class="space-y-2 text-sm font-mono text-neutral-800">
        <?php foreach ($pendingMigrations as $migration): ?>
          <li class="bg-white border border-neutral-200 rounded-md px-3 py-2"><?= e($migration) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <form method="post" action="<?= url('/admin/updates/run') ?>" onsubmit="return confirm('Run pending updates now?');">
      <?= csrfField() ?>
      <button type="submit" class="btn-primary">
        Run Updates
      </button>
    </form>
  <?php else: ?>
    <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-4 space-y-3">
      <p class="text-sm text-neutral-700">Everything is up to date. This page will show new migrations here when they are added.</p>
      <form method="post" action="<?= url('/admin/updates/run') ?>">
        <?= csrfField() ?>
        <button type="submit" class="btn-secondary">
          Check / run updates
        </button>
      </form>
    </div>
  <?php endif; ?>

  <div class="border border-neutral-200 rounded-lg overflow-hidden">
    <div class="flex items-center justify-between gap-3 bg-neutral-50 px-4 py-3">
      <p class="text-xs font-bold uppercase tracking-widest text-neutral-600">Migration history</p>
      <p class="text-xs font-bold text-neutral-500"><?= (int) $appliedCount ?> / <?= count($migrationStatus) ?> applied</p>
    </div>
    <div class="divide-y divide-neutral-100">
      <?php if (!$migrationStatus): ?>
        <p class="px-4 py-5 text-sm font-semibold text-neutral-600">No migration files were found.</p>
      <?php endif; ?>
      <?php foreach ($migrationStatus as $migration): ?>
        <div class="flex items-center justify-between gap-3 px-4 py-3">
          <span class="font-mono text-sm text-neutral-800"><?= e($migration['name']) ?></span>
          <?php if (!empty($migration['applied'])): ?>
            <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase text-white" style="background:var(--ke-green)">Applied</span>
          <?php else: ?>
            <span class="rounded-full px-2 py-1 text-[10px] font-black uppercase text-white" style="background:var(--ke-red)">Pending</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
