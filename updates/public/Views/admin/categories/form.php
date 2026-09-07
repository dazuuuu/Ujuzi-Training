<?php
/** Requires $category (nullable), $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
$actionUrl = $category ? url('/admin/categories/' . (int) $category['id']) : url('/admin/categories');
?>

<form method="post" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="max-w-2xl bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
  <?= csrfField() ?>

  <?php foreach ($errors as $err): ?>
    <div class="bg-rose-50 border border-rose-300 text-rose-800 text-sm font-semibold rounded-lg p-3"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="text-[11px] font-bold text-neutral-600 uppercase">Category Key <span class="text-neutral-400 font-normal normal-case">(used for filtering; can't change later)</span></label>
      <input type="text" name="category_key" value="<?= e($form['category_key']) ?>" <?= $category ? 'readonly' : 'placeholder="auto-generated from name if left blank"' ?> class="w-full mt-1 border border-neutral-300 rounded-lg p-2.5 text-sm <?= $category ? 'bg-neutral-100 text-neutral-500' : 'bg-white' ?> focus:outline-none focus:border-black" />
    </div>
    <div>
      <label class="text-[11px] font-bold text-neutral-600 uppercase">Sort Order</label>
      <input type="number" name="sort_order" value="<?= (int) $form['sort_order'] ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
    </div>
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Name</label>
    <input type="text" name="name" required value="<?= e($form['name']) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Tagline</label>
    <input type="text" name="tagline" value="<?= e($form['tagline']) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Cover Image</label>
    <?php if (!empty($category['image'])): ?>
      <img src="<?= e(imageUrl($category['image'])) ?>" alt="" class="w-40 h-28 object-cover rounded-lg border border-neutral-200 mt-2 mb-2" />
    <?php endif; ?>
    <input type="file" name="image" accept="image/*" class="w-full mt-1 text-sm" />
    <p class="text-[11px] text-neutral-400 mt-1">JPG, PNG, WEBP or GIF, up to 8MB. Leave empty to keep the current image.</p>
  </div>

  <div class="flex items-center gap-3 pt-3 border-t border-neutral-100">
    <button type="submit" class="bg-[#0a0a0a] hover:bg-black text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest transition-colors cursor-pointer border border-neutral-300">
      <?= $category ? 'Save Changes' : 'Create Category' ?>
    </button>
    <a href="<?= url('/admin/categories') ?>" class="text-xs font-bold text-neutral-500 hover:text-neutral-800">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
