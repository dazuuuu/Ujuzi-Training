<?php
/** Requires $role, $allRoles, $errors in scope. */
require __DIR__ . '/../layout-header.php';
?>

<form method="post" action="<?= url('/admin/roles/' . (int) $role['id']) ?>" class="max-w-3xl space-y-6">
  <?= csrfField() ?>

  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Role limits</h2>
    <p class="text-xs font-semibold text-neutral-600">Slug: <span class="font-mono"><?= e($role['slug']) ?></span></p>

    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Display name</label>
      <input type="text" name="name" required value="<?= e($role['name']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none"><?= e($role['description']) ?></textarea>
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Sort order</label>
      <input type="number" name="sort_order" value="<?= (int) $role['sort_order'] ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
    </div>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Power</h2>
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="has_admin_features" value="1" <?= !empty($role['has_admin_features']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Has admin-like features (organisation tools, managing people)
    </label>
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="is_under_organisation" value="1" <?= !empty($role['is_under_organisation']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Lives under organisation power
    </label>
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="can_manage_users" value="1" <?= !empty($role['can_manage_users']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Can create and edit users in their organisation
    </label>
    <div>
      <p class="text-[11px] font-bold uppercase text-neutral-600">Roles this user can manage</p>
      <div class="mt-2 grid gap-2 sm:grid-cols-2">
        <?php foreach ($allRoles as $option): if ($option['slug'] === $role['slug']) continue; ?>
          <label class="flex items-center gap-2 rounded-lg border border-neutral-300 bg-white p-2.5 text-sm font-semibold text-neutral-800">
            <input type="checkbox" name="managed_role_slugs[]" value="<?= e($option['slug']) ?>" <?= in_array($option['slug'], $role['managed_role_slugs'], true) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
            <?= e($option['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary">Save role</button>
    <a href="<?= url('/admin/roles') ?>" class="btn-secondary">Back to roles</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
