<?php
/** Requires $organisations, $invites in scope. */
require __DIR__ . '/../layout-header.php';
$invites = $invites ?? [];
$adminUsersByOrganisation = $adminUsersByOrganisation ?? [];
?>

<div class="space-y-6" data-live-search-scope>
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Tenants</p>
      <h2 class="mt-2 text-2xl font-black text-black">Organisations</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Every LMS user belongs to an organisation. Hover a row to invite an admin or edit the organisation.</p>
    </div>
    <a href="<?= url('/admin/organisations/create') ?>" class="btn-primary">Add organisation</a>
  </section>

  <div class="live-search-bar">
    <input type="text" data-live-search="table.excel-table" placeholder="Search organisations by name or admin email..." autocomplete="off" />
  </div>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-table admin-data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Admin login</th>
          <th>Status</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$organisations): ?>
          <tr><td colspan="4" class="px-5 py-8 text-center font-bold text-neutral-700">No organisations yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($organisations as $org): ?>
          <?php $adminUsers = $adminUsersByOrganisation[(int) $org['id']] ?? []; ?>
          <tr>
            <td>
              <a href="<?= url('/admin/organisations/' . (int) $org['id'] . '/edit') ?>" class="font-black text-black no-underline hover:underline"><?= e($org['name']) ?></a>
              <p class="text-xs font-semibold text-neutral-600"><?= e($org['description'] ?: 'No description') ?></p>
              <div class="excel-row-actions mt-1">
                <a href="<?= url('/admin/organisations/' . (int) $org['id'] . '/edit#invite') ?>" class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Invite admin</a>
                <a href="<?= url('/admin/organisations/' . (int) $org['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Edit</a>
                <form method="post" action="<?= url('/admin/organisations/' . (int) $org['id'] . '/delete') ?>" onsubmit="return confirm('Delete \'<?= e($org['name']) ?>\' permanently? This also deletes its courses, categories, branches, and memberships. This cannot be undone.');">
                  <?= csrfField() ?>
                  <button type="submit" class="btn-danger" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Delete</button>
                </form>
              </div>
            </td>
            <td>
              <?php if ($adminUsers): ?>
                <?php foreach ($adminUsers as $adminUser): ?>
                  <p class="text-xs font-bold text-neutral-700"><?= e($adminUser['email'] ?? '') ?></p>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-xs font-black uppercase" style="color:var(--ke-red)">No admin account</p>
              <?php endif; ?>
            </td>
            <td><?= !empty($org['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td><?= e(date('M j, Y', strtotime($org['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($organisations): ?>
    <section class="space-y-4" id="share">
      <div>
        <h3 class="font-serif-heading text-xl font-bold">Share organisation registration form</h3>
        <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Generate a one-use URL, copy it, or email it to a client. You can also open <a href="<?= url('/admin/share-registration') ?>" class="font-bold" style="color:var(--ke-green)">Share registration</a> in the Super Admin menu.</p>
      </div>
      <?php foreach ($organisations as $org):
        $organisation = $org;
        $invite = $invites[(int) $org['id']] ?? null;
        $returnTo = 'index';
        require __DIR__ . '/invite-panel.php';
      endforeach; ?>
    </section>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
