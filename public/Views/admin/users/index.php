<?php
/** Requires $users and $groupedUsers in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="registered-users-page space-y-4">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Directory</p>
      <h2 class="mt-2 text-2xl font-black text-black">Users</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Creating a user automatically provisions their dashboard and profile, then attaches every form assigned to their role.</p>
    </div>
    <a href="<?= url('/admin/users/create') ?>" class="btn-primary">Create user</a>
  </section>

  <div class="grid gap-4 md:grid-cols-[220px_minmax(0,1fr)] md:items-start">
  <aside class="rounded-xl border border-neutral-300 bg-white p-3 shadow-sm md:sticky md:top-4">
    <div class="flex items-center justify-between gap-3">
      <h3 class="font-serif-heading text-lg font-bold">Search and filter</h3>
      <a href="<?= url('/admin/registered-users') ?>" class="text-xs font-bold text-neutral-600">Clear</a>
    </div>
    <form method="get" action="<?= url('/admin/registered-users') ?>" class="mt-3 space-y-3">
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Search</label>
        <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Name, email, phone..." class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Role</label>
        <select name="role_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
          <option value="0">All roles</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>" <?= (int) ($filters['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Organisation</label>
        <select name="organisation_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
          <option value="0">All organisations</option>
          <?php foreach ($organisations as $organisation): ?>
            <option value="<?= (int) $organisation['id'] ?>" <?= (int) ($filters['organisation_id'] ?? 0) === (int) $organisation['id'] ? 'selected' : '' ?>><?= e($organisation['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Account status</label>
        <select name="status" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm">
          <option value="">All statuses</option>
          <?php foreach (['active' => 'Active', 'blocked' => 'Blocked', 'suspended' => 'Suspended'] as $status => $label): ?>
            <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-primary w-full">Apply filters</button>
    </form>
  </aside>

  <div class="space-y-4">
  <div class="flex items-center justify-between gap-3">
    <p class="text-sm font-bold text-neutral-600"><?= (int) $resultCount ?> matching user<?= $resultCount === 1 ? '' : 's' ?></p>
  </div>
  <?php foreach ($groupedUsers as $group): ?>
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="font-serif-heading text-xl font-bold"><?= e($group['name']) ?></h3>
      <span class="text-xs font-bold text-neutral-600"><?= count($group['users']) ?> registered</span>
    </div>
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
        <?php if (!$group['users']): ?>
          <tr><td colspan="6" class="px-5 py-8 text-center font-bold text-neutral-700">No users yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($group['users'] as $person): ?>
          <tr>
            <td class="px-5 py-4 font-black text-black"><?= e(userDisplayName($person)) ?></td>
            <td class="px-5 py-4 font-bold"><?= e($person['role_name']) ?></td>
            <td class="px-5 py-4 font-semibold text-neutral-700"><?= e($person['organisation_name'] ?? '—') ?></td>
            <td class="px-5 py-4 text-xs font-semibold text-neutral-700">
              <?= e($person['email'] ?: '—') ?><br><?= e($person['phone'] ?: '') ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= e(ucfirst((string) ($person['account_status'] ?? (!empty($person['is_active']) ? 'active' : 'blocked')))) ?></td>
            <td class="px-5 py-4">
              <a href="<?= url('/admin/users/' . (int) $person['id']) ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">View</a>
              <a href="<?= url('/admin/users/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
              <div class="mt-2 flex flex-wrap gap-1">
                <?php if (($person['account_status'] ?? '') !== 'active'): ?>
                  <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="active" /><button type="submit" class="text-xs font-bold text-emerald-700">Activate</button></form>
                <?php endif; ?>
                <?php if (($person['account_status'] ?? '') !== 'blocked'): ?>
                  <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="blocked" /><button type="submit" class="text-xs font-bold text-amber-700">Block</button></form>
                <?php endif; ?>
                <?php if (($person['account_status'] ?? '') !== 'suspended'): ?>
                  <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="suspended" /><button type="submit" class="text-xs font-bold text-orange-700">Suspend</button></form>
                <?php endif; ?>
                <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/delete') ?>" onsubmit="return confirm('Delete this user permanently?');"><?= csrfField() ?><button type="submit" class="text-xs font-bold text-rose-700">Delete</button></form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  </section>
  <?php endforeach; ?>
  <?php if (!$groupedUsers): ?>
    <div class="rounded-xl border border-neutral-300 bg-white p-8 text-center font-bold text-neutral-700">No registered users yet.</div>
  <?php endif; ?>
  </div>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
