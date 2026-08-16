<?php
/** Requires $user, $roles, $organisations, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$action = $user ? url('/admin/users/' . (int) $user['id']) : url('/admin/users');
?>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-6">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">User details</h2>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">First name</label>
        <input type="text" name="first_name" required value="<?= e($form['first_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Last name</label>
        <input type="text" name="last_name" value="<?= e($form['last_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Email</label>
        <input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Phone</label>
        <input type="tel" name="phone" value="<?= e($form['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Role</label>
        <select name="role_id" required class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none">
          <option value="">Choose a role</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>" <?= (int) ($form['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Organisation</label>
        <select name="organisation_id" required class="mt-1 w-full rounded-lg border border-neutral-300 bg-white p-2.5 text-sm focus:border-black focus:outline-none">
          <option value="">Choose an organisation</option>
          <?php foreach ($organisations as $org): ?>
            <option value="<?= (int) $org['id'] ?>" <?= (int) ($form['organisation_id'] ?? 0) === (int) $org['id'] ? 'selected' : '' ?>><?= e($org['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Active — can sign in
    </label>
    <p class="text-xs font-medium text-neutral-600">They sign in with the existing email code or phone login. Saving this form creates or refreshes their dashboard and profile pages.</p>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-black px-6 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900"><?= $user ? 'Save user' : 'Create user' ?></button>
    <a href="<?= url('/admin/users') ?>" class="text-xs font-black text-neutral-700 hover:text-black">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
