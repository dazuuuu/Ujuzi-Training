<?php
/** Requires $pendingMigrations in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-3xl bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
  <div>
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">System Updates</h2>
    <p class="text-sm text-neutral-500 mt-1">Run pending database migrations from the admin panel.</p>
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
      <button type="submit" class="bg-black hover:bg-neutral-900 text-white text-xs font-bold px-5 py-3 rounded-lg uppercase tracking-widest">
        Run Updates
      </button>
    </form>
  <?php else: ?>
    <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-4 text-sm text-neutral-700">
      Everything is up to date. The Update menu will appear automatically when new migrations are pending.
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
