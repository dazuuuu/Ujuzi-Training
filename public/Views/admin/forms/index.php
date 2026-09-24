<?php
/** Requires $forms in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Profile builder</p>
      <h2 class="mt-2 text-2xl font-black text-black">Forms</h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">Build WordPress-style forms. Profile forms appear on matching users’ profile pages. Course forms are filled by approved tutors to create courses. Include a Category field and document uploads on course forms.</p>
    </div>
    <a href="<?= url('/admin/forms/create') ?>" class="btn-primary">Create form</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-table admin-data-table">
      <thead>
        <tr>
          <th>Form</th>
          <th>Purpose</th>
          <th>Assigned to</th>
          <th>Fields</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$forms): ?>
          <tr><td colspan="6" class="px-5 py-8 text-center font-bold text-neutral-700">No forms yet. Create a profile form or a course creation form.</td></tr>
        <?php endif; ?>
        <?php foreach ($forms as $form): ?>
          <tr>
            <td class="font-black">
              <?= e($form['title']) ?>
              <p class="text-xs font-semibold text-neutral-600"><?= e($form['description'] ?: 'No description') ?></p>
            </td>
            <td><?= ($form['purpose'] ?? 'profile') === 'course' ? 'Course creation' : 'Profile details' ?></td>
            <td>
              <?php if (empty($form['roles'])): ?>
                Unassigned
              <?php else: ?>
                <?= e(implode(', ', array_column($form['roles'], 'name'))) ?>
              <?php endif; ?>
            </td>
            <td><?= (int) $form['field_count'] ?></td>
            <td><?= !empty($form['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td>
              <div class="excel-row-actions">
                <a href="<?= url('/admin/forms/' . (int) $form['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.5rem;font-size:0.7rem;">Edit</a>
                <form method="post" action="<?= url('/admin/forms/' . (int) $form['id'] . '/delete') ?>" onsubmit="return confirm('Delete this form and its saved answers?');">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-danger" style="padding:0.35rem 0.5rem;font-size:0.7rem;">Delete</button>
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
