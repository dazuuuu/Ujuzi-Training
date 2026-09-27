<?php
/** Requires $categories, $counts in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="max-w-3xl space-y-6">
  <section>
    <h1 class="font-serif-heading text-2xl font-bold">Data Cleanup</h1>
    <p class="mt-1 text-sm font-medium text-neutral-600">
      Permanently delete whole categories of live data — useful for clearing test data before going live. This cannot be undone; there is no backup taken automatically.
    </p>
  </section>

  <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
    <form method="post" action="<?= url('/admin/data-cleanup') ?>" onsubmit="return confirm('This permanently deletes the selected data. Continue?');" class="space-y-5">
      <?= csrfField() ?>

      <div class="space-y-3">
        <?php foreach ($categories as $key => $label): ?>
          <label class="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 p-3 cursor-pointer hover:bg-neutral-50">
            <span class="flex items-center gap-3">
              <input type="checkbox" name="categories[]" value="<?= e($key) ?>" class="h-4 w-4" />
              <span class="text-sm font-bold text-neutral-800"><?= e($label) ?></span>
            </span>
            <span class="text-xs font-black uppercase text-neutral-500"><?= (int) ($counts[$key] ?? 0) ?> record<?= (int) ($counts[$key] ?? 0) === 1 ? '' : 's' ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="rounded-lg border p-4 space-y-3" style="border-color:var(--ke-red)">
        <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-red)">Confirm deletion</p>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Type DELETE to confirm</label>
          <input type="text" name="confirm_phrase" required class="mt-1 block w-full rounded border border-neutral-300 p-2 text-sm" placeholder="DELETE" autocomplete="off" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-neutral-600">Your admin password</label>
          <input type="password" name="password" required class="mt-1 block w-full rounded border border-neutral-300 p-2 text-sm" autocomplete="current-password" />
        </div>
      </div>

      <button type="submit" class="btn-danger">Delete selected data</button>
    </form>
  </section>

  <section class="rounded-xl border-2 p-6 shadow-sm space-y-4" style="border-color:var(--ke-red)">
    <div>
      <h2 class="font-serif-heading text-lg font-bold" style="color:var(--ke-red)">Complete reset</h2>
      <p class="mt-1 text-sm font-medium text-neutral-600">
        Deletes every organisation, student, tutor, organisation admin, attachment provider, branch admin, course, branch, membership, and attachment application in the system — a full factory reset of the live data. Your Super Admin login, roles, and the profile form templates are kept so the platform is ready for the next tenant right away.
      </p>
    </div>

    <form method="post" action="<?= url('/admin/data-cleanup/reset-everything') ?>" onsubmit="return confirm('This deletes EVERY organisation, user, and their data. This cannot be undone. Continue?');">
      <?= csrfField() ?>
      <button type="submit" class="btn-danger">Reset the entire database</button>
    </form>
  </section>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
