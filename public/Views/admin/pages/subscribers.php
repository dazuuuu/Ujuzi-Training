<?php
/** Newsletter subscribers from the website footer. Requires $rows. */
require __DIR__ . '/../layout-header.php';
?>
<div class="max-w-3xl space-y-5">
  <section class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <a href="<?= url('/admin/pages') ?>" class="text-xs font-black uppercase tracking-widest text-neutral-600">← Pages</a>
      <h1 class="mt-1 text-2xl font-black text-black">Newsletter subscribers <span class="text-base text-neutral-500">(<?= count($rows) ?>)</span></h1>
      <p class="mt-1 text-sm font-medium text-neutral-700">Emails left in the newsletter box at the bottom of the website.</p>
    </div>
    <?php if ($rows): ?><a href="<?= url('/admin/pages/subscribers?export=xlsx') ?>" class="btn-primary">Download Excel</a><?php endif; ?>
  </section>
  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-sheet w-full">
      <thead><tr><th>#</th><th>Email</th><th>Subscribed</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?><tr><td><?= $i + 1 ?></td><td><a class="underline" href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></td><td><?= e(substr((string) $r['created_at'], 0, 10)) ?></td></tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="3" class="text-center font-bold text-neutral-500">No subscribers yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
