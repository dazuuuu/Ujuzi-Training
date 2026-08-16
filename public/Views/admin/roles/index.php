<?php
/** Requires $roles in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Access control</p>
      <h2 class="mt-2 text-2xl font-black text-black">Roles and limits</h2>
      <p class="mt-1 max-w-2xl text-sm font-medium text-neutral-700">The store owner is Super Admin. Everyone else is a user with one of these roles. Only Organisation Admins and Attachment Trainers have admin-like tools; trainers and students stay under organisation power.</p>
    </div>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="w-full min-w-[860px] text-left text-sm admin-data-table">
      <thead class="bg-neutral-100 text-[11px] uppercase tracking-wider text-black">
        <tr>
          <th class="px-5 py-3">Role</th>
          <th class="px-5 py-3">Admin-like tools</th>
          <th class="px-5 py-3">Under organisation</th>
          <th class="px-5 py-3">Can manage</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <tr>
          <td class="px-5 py-4">
            <p class="font-black text-black">Super Admin</p>
            <p class="mt-1 text-xs font-semibold text-neutral-600">Platform owner. Not a regular user — signs in at Admin login.</p>
          </td>
          <td class="px-5 py-4 font-bold">Full platform</td>
          <td class="px-5 py-4 font-bold">No</td>
          <td class="px-5 py-4 font-bold">Everyone</td>
          <td class="px-5 py-4 text-xs font-bold text-neutral-500">Fixed</td>
        </tr>
        <?php foreach ($roles as $role): ?>
          <tr>
            <td class="px-5 py-4">
              <p class="font-black text-black"><?= e($role['name']) ?></p>
              <p class="mt-1 text-xs font-semibold text-neutral-600"><?= e($role['description']) ?></p>
            </td>
            <td class="px-5 py-4 font-bold"><?= $role['has_admin_features'] ? 'Yes' : 'No' ?></td>
            <td class="px-5 py-4 font-bold"><?= $role['is_under_organisation'] ? 'Yes' : 'Owns the organisation' ?></td>
            <td class="px-5 py-4 font-bold">
              <?php if (!$role['managed_role_slugs']): ?>
                None
              <?php else: ?>
                <?= e(implode(', ', array_map('roleLabel', $role['managed_role_slugs']))) ?>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4">
              <a href="<?= url('/admin/roles/' . (int) $role['id'] . '/edit') ?>" class="text-xs font-black uppercase tracking-widest text-black hover:underline">Edit limits</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
