<?php
/** Requires $forms in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Submitted answers</p>
      <h2 class="mt-2 text-2xl font-black text-black">Form replies</h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">Open a form to read every answer people have saved on it, including organisation branches and other profile details.</p>
    </div>
    <a href="<?= url('/admin/forms') ?>" class="btn-secondary">Edit forms</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="w-full min-w-[720px] text-left text-sm admin-data-table">
      <thead class="bg-neutral-100 text-[11px] uppercase tracking-wider text-black">
        <tr>
          <th class="px-5 py-3">Form</th>
          <th class="px-5 py-3">Purpose</th>
          <th class="px-5 py-3">Assigned to</th>
          <th class="px-5 py-3">Replies</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$forms): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center font-bold text-neutral-700">No forms yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($forms as $form): ?>
          <tr>
            <td class="px-5 py-4">
              <p class="font-black text-black"><?= e($form['title']) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($form['description'] ?: 'No description') ?></p>
            </td>
            <td class="px-5 py-4 text-xs font-bold text-neutral-800"><?= ($form['purpose'] ?? 'profile') === 'course' ? 'Course creation' : 'Profile details' ?></td>
            <td class="px-5 py-4 text-xs font-bold text-neutral-800">
              <?= empty($form['roles']) ? 'Unassigned' : e(implode(', ', array_column($form['roles'], 'name'))) ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= (int) ($form['filled_count'] ?? 0) ?></td>
            <td class="px-5 py-4">
              <a href="<?= url('/admin/responses/' . (int) $form['id']) ?>" class="btn-primary" style="padding:0.4rem 0.7rem;">View details</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
