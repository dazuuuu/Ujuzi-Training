<?php
$branch = $branch ?? null;
$form = $form ?? ['title' => '', 'location' => '', 'cover_image' => '', 'sort_order' => 0];
$errors = $errors ?? [];
$isEdit = !empty($branch['id']);
$action = $isEdit ? url('/account/branches/' . (int) $branch['id']) : url('/account/branches');
require __DIR__ . '/../layout-header.php';
?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="max-w-3xl space-y-6">
  <?= csrfField() ?>
  <?php foreach ($errors as $err): ?>
    <div class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800"><?= e(is_array($err) ? implode(' ', $err) : $err) ?></div>
  <?php endforeach; ?>

  <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
    <p class="text-xs font-black uppercase tracking-widest" style="color:var(--ke-green)">Organisation</p>
    <h1 class="font-serif-heading text-2xl font-bold"><?= $isEdit ? 'Edit branch' : 'Add branch' ?></h1>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Title</label>
      <input type="text" name="title" required value="<?= e($form['title'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="e.g. Nairobi campus" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Location</label>
      <input type="text" name="location" required value="<?= e($form['location'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" placeholder="e.g. Westlands, Nairobi" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Cover image (optional)</label>
      <?php if (!empty($form['cover_image'])): ?>
        <img src="<?= e(imageUrl($form['cover_image'])) ?>" alt="" class="mt-2 max-h-36 rounded border border-neutral-200" />
      <?php endif; ?>
      <input type="file" name="cover_image" accept="image/*" class="mt-2 block w-full text-sm" />
    </div>
    <div>
      <label class="text-[11px] font-bold uppercase text-neutral-600">Sort order</label>
      <input type="number" name="sort_order" value="<?= (int) ($form['sort_order'] ?? 0) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm" />
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Save branch' : 'Create branch' ?></button>
    <a href="<?= url('/account/branches') ?>" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
