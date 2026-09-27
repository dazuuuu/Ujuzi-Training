<?php
/** Requires $users and $groupedUsers in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="registered-users-page space-y-4" data-live-search-scope>
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Directory</p>
      <h2 class="mt-2 text-2xl font-black text-black"><?= e($pageTitle ?? 'Users') ?></h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Creating a user automatically provisions their dashboard and profile, then attaches every form assigned to their role. Hover a row to view, edit, or change its status.</p>
    </div>
    <a href="<?= url('/admin/users/create') ?>" class="btn-primary">Create user</a>
  </section>

  <div class="live-search-bar">
    <input type="text" data-live-search="table.excel-table" placeholder="Search by name, role, organisation, email or phone..." autocomplete="off" />
  </div>

  <?php foreach ($groupedUsers as $group): ?>
  <section class="space-y-3" data-live-search-group>
    <div class="flex items-center justify-between">
      <h3 class="font-serif-heading text-xl font-bold"><?= e($group['name']) ?></h3>
      <span class="text-xs font-bold text-neutral-600"><?= count($group['users']) ?> registered</span>
    </div>
    <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
      <table class="excel-table admin-data-table">
        <thead>
          <tr class="excel-table-group">
            <th colspan="3">Account</th>
            <th colspan="2">Sign-in</th>
            <th>Status</th>
          </tr>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Organisation</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$group['users']): ?>
            <tr><td colspan="6" class="px-5 py-8 text-center font-bold text-neutral-700">No users yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($group['users'] as $person): ?>
            <tr>
              <td>
                <a href="<?= url('/admin/users/' . (int) $person['id']) ?>" class="font-black text-black no-underline hover:underline"><?= e(userDisplayName($person)) ?></a>
                <div class="excel-row-actions mt-1">
                  <a href="<?= url('/admin/users/' . (int) $person['id']) ?>" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">View</a>
                  <a href="<?= url('/admin/users/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Edit</a>
                  <?php if (($person['account_status'] ?? '') !== 'active'): ?>
                    <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="active" /><button type="submit" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;color:#047857;">Activate</button></form>
                  <?php endif; ?>
                  <?php if (($person['account_status'] ?? '') !== 'blocked'): ?>
                    <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="blocked" /><button type="submit" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;color:#b45309;">Block</button></form>
                  <?php endif; ?>
                  <?php if (($person['account_status'] ?? '') !== 'suspended'): ?>
                    <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/status') ?>"><?= csrfField() ?><input type="hidden" name="status" value="suspended" /><button type="submit" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;color:#c2410c;">Suspend</button></form>
                  <?php endif; ?>
                  <form method="post" action="<?= url('/admin/users/' . (int) $person['id'] . '/delete') ?>" onsubmit="return confirm('Delete this user permanently?');"><?= csrfField() ?><button type="submit" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;color:#be123c;">Delete</button></form>
                </div>
              </td>
              <td><?= e($person['role_name']) ?></td>
              <td><?= e($person['organisation_name'] ?? '—') ?></td>
              <td><?= e($person['email'] ?: '—') ?></td>
              <td><?= e($person['phone'] ?: '—') ?></td>
              <td><?= e(ucfirst((string) ($person['account_status'] ?? (!empty($person['is_active']) ? 'active' : 'blocked')))) ?></td>
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

<?php require __DIR__ . '/../layout-footer.php'; ?>
