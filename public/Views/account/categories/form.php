<?php
$category = $category ?? null;
$form = $form ?? ['name' => '', 'description' => '', 'is_active' => 1, 'sort_order' => 0];
$errors = $errors ?? [];
$isEdit = !empty($category['id']);
$action = $isEdit ? url('/account/categories/' . (int) $category['id']) : url('/account/categories');
require __DIR__ . '/../layout-header.php';
?>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-6">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e(is_array($err) ? implode(' ', $err) : $err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
    <h1 class="font-serif-heading text-2xl font-bold"><?= $isEdit ? 'Edit category' : 'Add category' ?></h1>
    <p class="text-sm font-medium text-neutral-600">Tutors approved by your organisation will see this option on the course creation form.</p>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Category name</label>
      <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="e.g. Hospitality" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Description</label>
      <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm"><?= e($form['description'] ?? '') ?></textarea>
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Sort order</label>
      <input type="number" name="sort_order" value="<?= (int) ($form['sort_order'] ?? 0) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
    </div>
    <input type="hidden" name="is_active" value="0" />
    <label class="flex items-center gap-2 text-sm font-bold">
      <input type="checkbox" name="is_active" value="1" <?= !empty($form['is_active']) ? 'checked' : '' ?> class="h-4 w-4" />
      Visible to approved tutors
    </label>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Save category' : 'Create category' ?></button>
    <a href="<?= url('/account/categories') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
