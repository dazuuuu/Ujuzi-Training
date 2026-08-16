<?php
/** Requires $users in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Directory</p>
      <h2 class="mt-2 text-2xl font-black text-black">Users</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Creating a user automatically provisions their dashboard and profile, then attaches every form assigned to their role.</p>
    </div>
    <a href="<?= url('/admin/users/create') ?>" class="btn-primary">Create user</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="w-full min-w-[860px] text-left text-sm admin-data-table">
      <thead class="bg-neutral-100 text-[11px] uppercase tracking-wider text-black">
        <tr>
          <th class="px-5 py-3">Name</th>
          <th class="px-5 py-3">Role</th>
          <th class="px-5 py-3">Organisation</th>
          <th class="px-5 py-3">Sign-in</th>
          <th class="px-5 py-3">Status</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$users): ?>
          <tr><td colspan="6" class="px-5 py-8 text-center font-bold text-neutral-700">No users yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $person): ?>
          <tr>
            <td class="px-5 py-4 font-black text-black"><?= e(userDisplayName($person)) ?></td>
            <td class="px-5 py-4 font-bold"><?= e($person['role_name']) ?></td>
            <td class="px-5 py-4 font-semibold text-neutral-700"><?= e($person['organisation_name'] ?? '—') ?></td>
            <td class="px-5 py-4 text-xs font-semibold text-neutral-700">
              <?= e($person['email'] ?: '—') ?><br><?= e($person['phone'] ?: '') ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= !empty($person['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td class="px-5 py-4">
              <a href="<?= url('/admin/users/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
