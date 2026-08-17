<?php
/** Role picker for LMS logins. Requires $roles in scope. */
require __DIR__ . '/layout-header.php';
?>

<div class="max-w-3xl mx-auto">
  <div class="text-center mb-8">
    <span class="text-xs font-bold uppercase tracking-widest block mb-1" style="color:var(--ke-green)">Choose your role</span>
    <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Sign in to your dashboard</h1>
    <p class="text-sm text-neutral-500 mt-2">Each role has its own login. After you sign in you land on that role’s dashboard.</p>
  </div>

  <div class="grid gap-4 sm:grid-cols-2">
    <?php if (!$roles): ?>
      <p class="sm:col-span-2 rounded-xl border border-neutral-200 bg-white p-6 text-sm font-bold text-neutral-600">Roles are not available yet. Run Super Admin setup first.</p>
    <?php endif; ?>
    <?php foreach ($roles as $role):
      $adminLike = !empty($role['has_admin_features']);
      $path = \App\Core\LoginRoles::loginPath((string) $role['slug']);
    ?>
      <a href="<?= url($path) ?>" class="rounded-xl bg-white p-5 no-underline transition-shadow hover:shadow-md" style="border:2px solid <?= $adminLike ? 'var(--ke-red)' : 'var(--ke-green)' ?>">
        <p class="text-[11px] font-black uppercase tracking-widest" style="color: <?= $adminLike ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= $adminLike ? 'Admin-like' : 'Under organisation' ?></p>
        <h2 class="mt-2 text-lg font-black text-black"><?= e($role['name']) ?></h2>
        <p class="mt-2 text-sm font-medium" style="color:var(--ke-muted)"><?= e($role['description'] ?? 'Sign in to this role’s dashboard.') ?></p>
        <p class="mt-4 text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Open <?= e($role['name']) ?> login →</p>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-8 rounded-xl border border-neutral-200 bg-neutral-50 p-5 text-center">
    <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Platform owner</p>
    <p class="mt-2 text-sm font-semibold text-neutral-700">Super Admin uses a separate login and dashboard.</p>
    <a href="<?= url('/admin/login') ?>" class="btn-danger mt-4 inline-flex" style="padding:0.45rem 0.9rem;">Super Admin login</a>
  </div>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
