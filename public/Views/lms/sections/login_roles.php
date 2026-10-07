<?php $loginRoles = $roles ?? []; ?>
<div class="pb-roles">
  <?php if (!$loginRoles): ?>
    <p class="pb-box" style="margin:0">Roles are not available yet. Run Super Admin setup first.</p>
  <?php endif; ?>
  <?php foreach ($loginRoles as $role):
    $adminLike = !empty($role['has_admin_features']);
  ?>
    <a href="<?= url(\App\Core\LoginRoles::loginPath((string) $role['slug'])) ?>" class="pb-role" style="<?= $adminLike ? 'border-color:var(--ke-red,#dc2626)' : '' ?>">
      <span class="pb-role-tag" style="<?= $adminLike ? 'color:var(--ke-red,#dc2626)' : '' ?>"><?= $adminLike ? 'Admin-like' : 'Under organisation' ?></span>
      <h2><?= e($role['name']) ?></h2>
      <p><?= e($role['description'] ?? 'Sign in to this role’s dashboard.') ?></p>
      <span class="pb-role-go">Open <?= e($role['name']) ?> login →</span>
    </a>
  <?php endforeach; ?>
</div>
<p class="pb-note" style="text-align:center;opacity:1"><a href="<?= url('/account/forgot-password') ?>" style="color:var(--pb-accent)">Forgot password?</a></p>
<?php if (($c['note'] ?? '') !== ''): ?><div class="pb-box"><?= nl2br(e($c['note'])) ?></div><?php endif; ?>
<?php if (!empty($c['show_admin_box'])): ?>
  <div class="pb-box">
    <p style="font-size:.72em;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:#6b7280">Platform owner</p>
    <p style="margin-top:6px">Super Admin uses a separate login and dashboard.</p>
    <a href="<?= url('/admin/login') ?>" class="btn-danger" style="display:inline-flex;margin-top:12px;padding:0.45rem 0.9rem;color:#fff;">Super Admin login</a>
  </div>
<?php endif; ?>
