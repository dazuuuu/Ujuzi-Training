<?php
/** Requires $users, $manageableRoles, $directoryMode in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="space-y-6">
  <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">People directory</p>
      <h1 class="mt-2 font-serif-heading text-3xl font-bold"><?= $directoryMode === 'trainer' ? 'Enrolled students' : ($directoryMode === 'attachment_trainer' ? 'Selected students' : 'People') ?></h1>
      <p class="mt-1 text-sm font-medium text-neutral-600"><?= $directoryMode === 'trainer' ? 'Students who have started one of your courses.' : ($directoryMode === 'attachment_trainer' ? 'Students who selected you as their attachment provider.' : 'People connected to ' . ($currentUser['organisation_name'] ?? 'your organisation') . '.') ?></p>
    </div>
    <?php if ($directoryMode !== 'trainer' && $directoryMode !== 'attachment_trainer'): ?>
      <a href="<?= url('/account/people/create') ?>" class="btn-primary">Add person</a>
    <?php endif; ?>
  </section>

  <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead class="text-[11px] uppercase tracking-wider" style="background:#e8f5ee;color:var(--ke-green-dark)">
        <tr>
          <th class="px-5 py-3">Name</th>
          <th class="px-5 py-3">Role</th>
          <th class="px-5 py-3">Sign-in</th>
          <th class="px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-100">
        <?php if (!$users): ?>
          <tr><td colspan="4" class="px-5 py-8 text-center font-bold text-neutral-600">No people in your scope yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $person): ?>
          <tr>
            <td class="px-5 py-4 font-black"><?= e(userDisplayName($person)) ?></td>
            <td class="px-5 py-4 font-bold"><?= e($person['role_name']) ?></td>
            <td class="px-5 py-4 text-xs font-semibold text-neutral-700"><?= e($person['email'] ?: $person['phone'] ?: '—') ?></td>
            <td class="px-5 py-4">
              <a href="<?= url('/account/people/' . (int) $person['id']) ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
