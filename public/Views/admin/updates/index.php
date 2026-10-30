<?php
/** Requires $pendingMigrations, $pendingDetails, $appliedDetails, $migrationStatus in scope. */
require __DIR__ . '/../layout-header.php';
$pendingMigrations = $pendingMigrations ?? [];
$pendingDetails = $pendingDetails ?? [];
$appliedDetails = $appliedDetails ?? [];
$migrationStatus = $migrationStatus ?? [];
$appliedCount = count(array_filter($migrationStatus, fn(array $row): bool => !empty($row['applied'])));
$renderNotes = static function (array $items): void {
    foreach ($items as $item): ?>
      <li class="py-2.5">
        <p class="text-sm font-bold text-neutral-900"><?= e($item['title']) ?></p>
        <?php if ($item['notes']): ?>
          <ul class="mt-1 list-disc space-y-0.5 pl-5 text-sm text-neutral-600">
            <?php foreach ($item['notes'] as $note): ?><li><?= e($note) ?></li><?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-xs text-neutral-500">Internal improvement — nothing new to learn.</p>
        <?php endif; ?>
      </li>
    <?php endforeach;
};
?>

<div class="max-w-3xl space-y-4">
  <?php if ($appliedDetails): ?>
    <section class="rounded-lg border bg-white p-4" style="border-color:var(--ke-green)">
      <h2 class="text-sm font-black uppercase tracking-wide" style="color:var(--ke-green)">What's new — just installed</h2>
      <p class="text-xs text-neutral-500">What changed and what you can do now.</p>
      <ul class="mt-1 divide-y divide-neutral-100"><?php $renderNotes($appliedDetails); ?></ul>
    </section>
  <?php endif; ?>

  <section class="rounded-lg border border-neutral-200 bg-white p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="text-base font-bold">System updates</h2>
        <p class="text-xs text-neutral-500"><?= $pendingMigrations ? count($pendingMigrations) . ' update(s) ready — read what each one does, then install.' : 'Everything is up to date.' ?></p>
      </div>
      <form method="post" action="<?= url('/admin/updates/run') ?>" <?= $pendingMigrations ? 'onsubmit="return confirm(\'Install these updates now?\');"' : '' ?>>
        <?= csrfField() ?>
        <button type="submit" class="<?= $pendingMigrations ? 'btn-primary' : 'btn-secondary' ?>"><?= $pendingMigrations ? 'Install updates' : 'Check for updates' ?></button>
      </form>
    </div>
    <?php if ($pendingDetails): ?>
      <ul class="mt-2 divide-y divide-neutral-100 border-t border-neutral-100"><?php $renderNotes($pendingDetails); ?></ul>
    <?php endif; ?>
  </section>

  <details class="rounded-lg border border-neutral-200 bg-white">
    <summary class="flex cursor-pointer items-center justify-between px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-neutral-600">
      History <span class="font-semibold normal-case text-neutral-500"><?= (int) $appliedCount ?> / <?= count($migrationStatus) ?> installed</span>
    </summary>
    <div class="divide-y divide-neutral-100 border-t border-neutral-100">
      <?php foreach (array_reverse($migrationStatus) as $migration): ?>
        <div class="flex items-center justify-between gap-3 px-4 py-1.5">
          <span class="font-mono text-xs text-neutral-700"><?= e($migration['name']) ?></span>
          <span class="text-[10px] font-black uppercase" style="color:<?= !empty($migration['applied']) ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= !empty($migration['applied']) ? 'Installed' : 'Pending' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </details>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
