<?php
/** Requires $users, $manageableRoles, $directoryMode in scope. */
require __DIR__ . '/../layout-header.php';
$attachmentApplications = $attachmentApplications ?? [];
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">People directory</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $directoryMode === 'trainer' ? 'Enrolled students' : ($directoryMode === 'attachment_trainer' ? 'Selected students' : 'People') ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= $directoryMode === 'trainer' ? 'Students who have started one of your courses.' : ($directoryMode === 'attachment_trainer' ? 'Students who selected you as their attachment provider.' : 'People connected to ' . ($currentUser['organisation_name'] ?? 'your organisation') . '.') ?></p>
    </div>
    <?php if ($directoryMode !== 'trainer' && $directoryMode !== 'attachment_trainer'): ?>
      <a href="<?= url('/account/people/import') ?>" class="btn-secondary">Import CSV</a>
      <a href="<?= url('/account/people/create') ?>" class="btn-primary">Add person</a>
    <?php endif; ?>
  </section>

  <?php if ($directoryMode === 'attachment_trainer' && !$attachmentApplications): ?>
    <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No student has chosen your organisation yet.</p>
  <?php endif; ?>

  <?php if ($directoryMode === 'attachment_trainer' && $attachmentApplications): ?>
    <section class="space-y-3">
      <div>
        <h2 class="font-serif-heading text-xl font-bold">Attachment applications</h2>
        <p class="mt-1 text-sm font-medium text-neutral-600">Students who chose a branch are handled by that branch's admin. Students who chose your organisation directly (no branch) are accepted and completed here — completing one automatically generates the student's recommendation letter.</p>
      </div>
      <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
        <table class="excel-table admin-data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Branch</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($attachmentApplications as $application):
              $status = (string) ($application['status'] ?? 'pending');
              $studentName = trim((string) ($application['first_name'] ?? '') . ' ' . (string) ($application['last_name'] ?? '')) ?: (string) ($application['email'] ?? 'Student');
            ?>
              <tr>
                <td class="font-black"><?= e($studentName) ?></td>
                <td><?= e($application['branch_title'] ?? 'Organisation') ?><?= !empty($application['branch_location']) ? ' - ' . e($application['branch_location']) : '' ?></td>
                <td><span class="text-[11px] font-black uppercase" style="color:var(--ke-green)"><?= e(ucfirst($status)) ?></span></td>
                <td>
                  <?php if (!empty($application['branch_id'])): ?>
                    <span class="text-xs font-bold text-neutral-500">Handled by branch admin</span>
                  <?php elseif ($status === 'pending'): ?>
                    <form method="post" action="<?= url('/account/attachments/' . (int) $application['id'] . '/accept') ?>"><?= csrfField() ?><button class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Accept</button></form>
                  <?php elseif ($status === 'accepted'): ?>
                    <form method="post" action="<?= url('/account/attachments/' . (int) $application['id'] . '/complete') ?>" onsubmit="return confirm('Mark this attachment complete? This generates their recommendation letter right away.');"><?= csrfField() ?><button class="btn-primary" style="padding:0.25rem 0.5rem;font-size:0.7rem;">Mark complete</button></form>
                  <?php elseif ($status === 'recommended'): ?>
                    <span class="text-xs font-bold" style="color:var(--ke-green)">Letter ready.</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($directoryMode !== 'attachment_trainer'): ?>
  <div class="live-search-bar">
    <input type="text" data-live-search="table.excel-table" placeholder="Search by name, role, or email/phone..." autocomplete="off" />
  </div>

  <div class="overflow-x-auto rounded-xl border border-neutral-300 bg-white shadow-sm">
    <table class="excel-table admin-data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Role</th>
          <th>Sign-in</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center font-bold text-neutral-700">No people in your scope yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $person): ?>
          <tr>
            <td class="font-black"><?= e(userDisplayName($person)) ?></td>
            <td><?= e($person['role_name']) ?></td>
            <td><?= e($person['email'] ?: $person['phone'] ?: '—') ?></td>
            <td>
              <span class="text-[11px] font-black uppercase" style="color: <?= ($person['account_status'] ?? 'active') === 'active' ? 'var(--ke-green)' : 'var(--ke-red)' ?>">
                <?= e(ucfirst((string) ($person['account_status'] ?? 'active'))) ?>
              </span>
            </td>
            <td>
              <?php if ($directoryMode === 'attachment_trainer' || $directoryMode === 'trainer'): ?>
                <span class="text-xs font-bold text-neutral-500">Tracked above</span>
              <?php else: ?>
                <a href="<?= url('/account/people/' . (int) $person['id']) ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">View</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
