<?php
/** Requires $roles in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Access control</p>
      <h2 class="mt-2 text-2xl font-black text-black">Roles and limits</h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">The store owner is Super Admin. Everyone else is a user with one of these roles. Only Organisations providing courses and Organisations providing Attachment have admin-like tools; tutors and students stay under organisation power.</p>
    </div>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-table admin-data-table">
      <thead>
        <tr>
          <th>Role</th>
          <th>Admin-like tools</th>
          <th>Under organisation</th>
          <th>Can manage</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="font-black">
            Super Admin
            <p class="mt-1 text-xs font-semibold text-neutral-600">Platform owner. Not a regular user — signs in at Admin login.</p>
          </td>
          <td>Full platform</td>
          <td>No</td>
          <td>Everyone</td>
          <td class="text-xs font-bold text-neutral-500">Fixed</td>
        </tr>
        <?php foreach ($roles as $role): ?>
          <tr>
            <td class="font-black">
              <?= e($role['name']) ?>
              <p class="mt-1 text-xs font-semibold text-neutral-600"><?= e($role['description']) ?></p>
            </td>
            <td><?= $role['has_admin_features'] ? 'Yes' : 'No' ?></td>
            <td><?= $role['is_under_organisation'] ? 'Yes' : 'Owns the organisation' ?></td>
            <td>
              <?php if (!$role['managed_role_slugs']): ?>
                None
              <?php else: ?>
                <?= e(implode(', ', array_map('roleLabel', $role['managed_role_slugs']))) ?>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= url('/admin/roles/' . (int) $role['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit limits</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
