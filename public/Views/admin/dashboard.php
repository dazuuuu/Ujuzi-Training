<?php
/** Requires $stats, $roleCounts, $recentUsers, $forms in scope. */
require __DIR__ . '/layout-header.php';
$today = date('j M Y');
$attachmentStats = $attachmentStats ?? [];
$attachmentRecent = $attachmentRecent ?? [];
$attachmentOrgCount = $attachmentOrgCount ?? 0;
$branchCount = $branchCount ?? 0;
$branchAdminCount = $branchAdminCount ?? 0;
$kpis = [
    [
        'label' => 'Users',
        'value' => number_format((int) $stats['users']),
        'meta' => number_format((int) $stats['organisations']) . ' organisations',
        'href' => url('/admin/users'),
    ],
    [
        'label' => 'Roles',
        'value' => number_format((int) $stats['roles']),
        'meta' => 'Limits live in Roles',
        'href' => url('/admin/roles'),
    ],
    [
        'label' => 'Forms',
        'value' => number_format((int) $stats['forms']),
        'meta' => 'Assigned to role profiles',
        'href' => url('/admin/forms'),
    ],
    [
        'label' => 'Profile fills',
        'value' => number_format((int) $stats['profilesCompleted']),
        'meta' => $stats['profileRate'] . '% of assigned forms',
        'href' => url('/admin/forms'),
    ],
];
$maxRole = max(1, ...array_map(fn($row) => (int) $row['total'], $roleCounts ?: [['total' => 1]]));
?>

<div class="space-y-6">
  <?php if (!empty($pendingMigrations)): ?>
    <section class="rounded-xl p-5 text-white" style="background:var(--ke-red)">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-widest">System update</p>
          <h2 class="mt-1 text-xl font-black"><?= count($pendingMigrations) ?> pending update<?= count($pendingMigrations) === 1 ? '' : 's' ?></h2>
          <p class="mt-1 text-sm font-medium" style="color:#ffd0d0">Run this once. The button disappears automatically when everything is up to date.</p>
        </div>
        <form method="post" action="<?= url('/admin/updates/run') ?>">
          <?= csrfField() ?>
          <button type="submit" class="btn-primary" style="background:#fff;color:var(--ke-red)">Update now</button>
        </form>
      </div>
    </section>
  <?php endif; ?>
  <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Dashboard / LMS</p>
      <h2 class="mt-2 text-3xl font-black tracking-tight" style="color:var(--ke-black)">Welcome back, Super Admin</h2>
      <p class="mt-1 text-sm font-medium" style="color:var(--ke-muted)">Create roles, assign forms, and share a one-use organisation registration form with a client.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-bold text-neutral-800"><?= e($today) ?></span>
      <a href="<?= url('/admin/share-registration') ?>" class="btn-primary">Share registration form</a>
      <a href="<?= url('/admin/forms/create') ?>" class="btn-secondary">Create form</a>
    </div>
  </section>

  <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($kpis as $card): ?>
      <a href="<?= e($card['href']) ?>" class="rounded-xl bg-white p-5 shadow-sm transition" style="border:2px solid var(--ke-green)">
        <p class="text-[11px] font-black uppercase tracking-widest" style="color:var(--ke-green)"><?= e($card['label']) ?></p>
        <p class="mt-3 text-2xl font-black tracking-tight text-black"><?= e($card['value']) ?></p>
        <p class="mt-2 text-xs font-bold text-neutral-700"><?= e($card['meta']) ?></p>
      </a>
    <?php endforeach; ?>
  </section>

  <?php require __DIR__ . '/partials/dashboard-charts.php'; ?>

  <?php if ($attachmentStats): ?>
    <section class="rounded-xl border border-neutral-300 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Attachment tracking</p>
          <h3 class="mt-1 text-xl font-black text-black">Provider workflow</h3>
        </div>
        <a href="<?= url('/admin/attachments') ?>" class="btn-secondary" style="padding:0.4rem 0.7rem;">View all attachments</a>
      </div>
      <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-neutral-200 p-3">
          <p class="text-[10px] font-black uppercase text-neutral-500">Attachment providers</p>
          <p class="mt-2 text-2xl font-black"><?= number_format($attachmentOrgCount) ?></p>
        </div>
        <div class="rounded-lg border border-neutral-200 p-3">
          <p class="text-[10px] font-black uppercase text-neutral-500">Branches</p>
          <p class="mt-2 text-2xl font-black"><?= number_format($branchCount) ?></p>
        </div>
        <div class="rounded-lg border border-neutral-200 p-3">
          <p class="text-[10px] font-black uppercase text-neutral-500">Branch admins assigned</p>
          <p class="mt-2 text-2xl font-black"><?= number_format($branchAdminCount) ?></p>
        </div>
      </div>
      <?php if ($attachmentRecent): ?>
        <div class="mt-4">
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Recent activity</p>
          <div class="mt-2 overflow-x-auto rounded-lg border border-neutral-200">
            <table class="excel-table admin-data-table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Course</th>
                  <th>Provider</th>
                  <th>Branch</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($attachmentRecent as $row):
                  $studentName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')) ?: (string) ($row['email'] ?? '');
                  $providerName = trim((string) ($row['provider_first_name'] ?? '') . ' ' . (string) ($row['provider_last_name'] ?? '')) ?: (string) ($row['provider_email'] ?? '');
                ?>
                  <tr>
                    <td class="font-black"><?= e($studentName) ?></td>
                    <td><?= e($row['course_title'] ?? '') ?></td>
                    <td><?= e($providerName) ?></td>
                    <td><?= e($row['branch_title'] ?? '—') ?></td>
                    <td class="font-bold uppercase"><?= e($row['status'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
