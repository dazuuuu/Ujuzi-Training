<?php
/** Requires $stats, $roleCounts, $recentUsers, $forms in scope. */
require __DIR__ . '/layout-header.php';
$today = date('j M Y');
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
        'href' => url('/admin/responses'),
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

  <section class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="border-b border-neutral-200 px-5 py-4">
        <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">People by role</p>
        <h3 class="mt-1 text-xl font-black text-black">Who is on the platform</h3>
      </div>
      <div class="space-y-4 p-5">
        <?php if (!$roleCounts): ?>
          <p class="text-sm font-bold text-neutral-700">Roles will appear after the first LMS update is run.</p>
        <?php endif; ?>
        <?php foreach ($roleCounts as $row):
          $width = max(6, (int) round(((int) $row['total'] / $maxRole) * 100));
        ?>
          <div>
            <div class="mb-1 flex items-center justify-between gap-3">
              <p class="truncate text-sm font-black text-black"><?= e($row['name']) ?></p>
              <p class="text-xs font-bold text-neutral-700"><?= (int) $row['total'] ?></p>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-neutral-200">
              <div class="h-full rounded-full" style="width: <?= $width ?>%;background:var(--ke-green)"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Recent users</p>
          <h3 class="mt-1 text-lg font-black text-black">Newly provisioned</h3>
        </div>
        <a href="<?= url('/admin/users') ?>" class="btn-secondary" style="padding:0.4rem 0.7rem;">View all</a>
      </div>
      <div class="divide-y divide-neutral-100">
        <?php if (!$recentUsers): ?>
          <p class="p-5 text-sm font-bold text-neutral-700">No users yet. Create an organisation, then add people.</p>
        <?php endif; ?>
        <?php foreach ($recentUsers as $person): ?>
          <div class="flex items-center justify-between gap-3 px-5 py-4">
            <div>
              <p class="text-sm font-black text-black"><?= e(userDisplayName($person)) ?></p>
              <p class="text-xs font-semibold text-neutral-600"><?= e($person['role_name']) ?> · <?= e($person['organisation_name'] ?? 'No organisation') ?></p>
            </div>
            <a href="<?= url('/admin/users/' . (int) $person['id'] . '/edit') ?>" class="btn-secondary" style="padding:0.35rem 0.65rem;">Edit</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
