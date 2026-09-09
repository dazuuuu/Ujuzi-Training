<?php
/** Requires $organisations, $invites in scope. */
require __DIR__ . '/../layout-header.php';
$invites = $invites ?? [];
$adminUsersByOrganisation = $adminUsersByOrganisation ?? [];
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Tenants</p>
      <h2 class="mt-2 text-2xl font-black text-black">Organisations</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Every LMS user belongs to an organisation. Generate a 5-minute, one-use registration URL to invite an organisation admin, or edit the organisation.</p>
    </div>
    <a href="<?= url('/admin/organisations/create') ?>" class="btn-primary">Add organisation</a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="w-full min-w-[640px] text-left text-sm admin-data-table">
      <thead class="bg-neutral-100 text-[11px] uppercase tracking-wider text-black">
        <tr>
          <th class="px-5 py-3">Name</th>
          <th class="px-5 py-3">Admin login</th>
          <th class="px-5 py-3">Status</th>
          <th class="px-5 py-3">Created</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$organisations): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center font-bold text-neutral-700">No organisations yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($organisations as $org): ?>
          <?php $adminUsers = $adminUsersByOrganisation[(int) $org['id']] ?? []; ?>
          <tr>
            <td class="px-5 py-4">
              <p class="font-black text-black"><?= e($org['name']) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($org['description'] ?: 'No description') ?></p>
            </td>
            <td class="px-5 py-4">
              <?php if ($adminUsers): ?>
                <?php foreach ($adminUsers as $adminUser): ?>
                  <p class="text-xs font-bold text-neutral-700"><?= e($adminUser['email'] ?? '') ?></p>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-xs font-black uppercase" style="color:var(--ke-red)">No admin account</p>
              <?php endif; ?>
            </td>
            <td class="px-5 py-4 font-bold"><?= !empty($org['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td class="px-5 py-4 font-semibold text-neutral-700"><?= e(date('M j, Y', strtotime($org['created_at']))) ?></td>
            <td class="px-5 py-4">
              <div class="flex flex-wrap items-center gap-2">
                <a href="<?= url('/admin/organisations/' . (int) $org['id'] . '/edit#invite') ?>" class="btn-primary" style="padding:0.35rem 0.65rem;">Invite admin</a>
                <a href="<?= url('/admin/organisations/' . (int) $org['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
              </div>
            </td>
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
