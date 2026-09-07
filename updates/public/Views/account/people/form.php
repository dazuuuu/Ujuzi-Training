<?php
/** Requires $person, $roles, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$action = $person ? url('/account/people/' . (int) $person['id']) : url('/account/people');
?>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-6">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <h1 class="font-serif-heading text-2xl font-bold"><?= $person ? 'Edit person' : 'Add person' ?></h1>
    <p class="text-sm font-medium text-neutral-600">They will get a dashboard and profile automatically, with forms assigned to their role.</p>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">First name</label>
        <input type="text" name="first_name" required value="<?= e($form['first_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Last name</label>
        <input type="text" name="last_name" value="<?= e($form['last_name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Email</label>
        <input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-neutral-600">Phone</label>
        <input type="tel" name="phone" value="<?= e($form['phone'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:border-black focus:outline-none" />
      </div>
      <div class="sm:col-span-2">
        <label class="text-[11px] font-bold uppercase text-neutral-600">Role</label>
        <select name="role_id" required class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm focus:border-black focus:outline-none">
          <option value="">Choose a role you can manage</option>
          <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>" <?= (int) ($form['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4 accent-black" />
      Active
    </label>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $person ? 'Save person' : 'Create person' ?></button>
    <a href="<?= url('/account/people') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
