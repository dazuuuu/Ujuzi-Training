<?php
/** Requires $organisation, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$action = $organisation ? url('/admin/organisations/' . (int) $organisation['id']) : url('/admin/organisations');
$adminUsers = $adminUsers ?? [];
?>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-6">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Organisation</h2>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Name</label>
      <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none"><?= e($form['description'] ?? '') ?></textarea>
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Active
    </label>
  </div>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <div class="border-b border-neutral-100 pb-3">
      <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">Organisation admin account</h2>
      <p class="mt-1 text-xs font-semibold text-neutral-600">An organisation row cannot sign in by itself. Add an Organisation Admin user here, or invite one from the panel below.</p>
    </div>
    <?php if ($adminUsers): ?>
      <div class="rounded-lg bg-neutral-50 p-3">
        <p class="text-[11px] font-black uppercase text-neutral-600">Existing admin logins</p>
        <ul class="mt-2 space-y-1 text-sm font-semibold text-neutral-800">
          <?php foreach ($adminUsers as $adminUser): ?>
            <li><?= e(trim(($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')) ?: 'Organisation Admin') ?> · <?= e($adminUser['email'] ?? '') ?><?= empty($adminUser['is_active']) ? ' · inactive' : '' ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Admin first name</label>
        <input type="text" name="admin_first_name" value="<?= e($form['admin_first_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Admin last name</label>
        <input type="text" name="admin_last_name" value="<?= e($form['admin_last_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Admin email</label>
        <input type="email" name="admin_email" value="<?= e($form['admin_email'] ?? '') ?>" placeholder="admin@example.com" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Admin password</label>
        <input type="password" name="admin_password" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
    </div>
    <p class="text-xs font-medium text-neutral-600">Leave these fields blank if you only want to save organisation details. If you enter an admin email, password is required.</p>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $organisation ? 'Save organisation' : 'Create organisation' ?></button>
    <a href="<?= url('/admin/organisations') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php
if ($organisation) {
    $invite = $invite ?? null;
    $freshInvite = $freshInvite ?? null;
    $returnTo = 'edit';
    echo '<div class="max-w-3xl mt-6">';
    require __DIR__ . '/invite-panel.php';
    echo '</div>';
}
?>

<?php require __DIR__ . '/../layout-footer.php'; ?>
