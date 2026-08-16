<?php
/** Requires $forms in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Profile builder</p>
      <h2 class="mt-2 text-2xl font-black text-black">Forms</h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">Build registration-style forms, assign them to roles, and they appear on each matching user’s profile for filling and re-editing.</p>
    </div>
    <a href="<?= url('/admin/forms/create') ?>" class="rounded-lg bg-black px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900">Create form</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="w-full min-w-[760px] text-left text-sm admin-data-table">
      <thead class="bg-neutral-100 text-[11px] uppercase tracking-wider text-black">
        <tr>
          <th class="px-5 py-3">Form</th>
          <th class="px-5 py-3">Assigned to</th>
          <th class="px-5 py-3">Fields</th>
          <th class="px-5 py-3">Status</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$forms): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center font-bold text-neutral-700">No forms yet. Create one to collect profile details.</td></tr>
        <?php endif; ?>
        <?php foreach ($forms as $form): ?>
          <tr>
            <td class="px-5 py-4">
              <p class="font-black text-black"><?= e($form['title']) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($form['description'] ?: 'No description') ?></p>
            </td>
            <td class="px-5 py-4 text-xs font-bold text-neutral-800">
              <?php if (empty($form['roles'])): ?>
                Unassigned
              <?php else: ?>
                <?= e(implode(', ', array_column($form['roles'], 'name'))) ?>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= (int) $form['field_count'] ?></td>
            <td class="px-5 py-4 font-bold"><?= !empty($form['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <a href="<?= url('/admin/forms/' . (int) $form['id'] . '/edit') ?>" class="text-xs font-black uppercase tracking-widest text-black hover:underline">Edit</a>
                <form method="post" action="<?= url('/admin/forms/' . (int) $form['id'] . '/delete') ?>" onsubmit="return confirm('Delete this form and its saved answers?');">
                  <?= csrfField() ?>
                  <button type="submit" class="text-xs font-black uppercase tracking-widest text-rose-700 hover:underline">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
