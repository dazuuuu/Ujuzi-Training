<?php
/** Requires $organisation, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$action = $organisation ? url('/admin/organisations/' . (int) $organisation['id']) : url('/admin/organisations');
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

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $organisation ? 'Save organisation' : 'Create organisation' ?></button>
    <a href="<?= url('/admin/organisations') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
