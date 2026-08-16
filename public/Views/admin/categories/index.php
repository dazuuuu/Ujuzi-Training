<?php
/** Requires $categories in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="flex items-center justify-between mb-5">
  <p class="text-sm text-neutral-500">Manage the categories products can be assigned to, and their cover images.</p>
  <a href="<?= url('/admin/categories/create') ?>" class="bg-[#0a0a0a] text-white text-xs font-bold px-4 py-2.5 rounded-lg uppercase tracking-widest hover:bg-black transition-colors border border-neutral-300">+ Add Category</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($categories as $cat): ?>
    <div class="bg-white border border-neutral-200 rounded-xl overflow-hidden shadow-sm">
      <div class="h-32 bg-neutral-100 relative">
        <?php if ($cat['image']): ?>
          <img src="<?= e(imageUrl($cat['image'])) ?>" alt="<?= e($cat['name']) ?>" class="w-full h-full object-cover" />
        <?php else: ?>
          <div class="w-full h-full flex items-center justify-center text-neutral-300 text-xs font-semibold uppercase">No cover image</div>
        <?php endif; ?>
        <span class="absolute top-2 left-2 bg-black/70 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded"><?= e($cat['category_key']) ?></span>
      </div>
      <div class="p-4">
        <h3 class="font-serif-heading text-base font-bold text-[#0a0a0a]"><?= e($cat['name']) ?></h3>
        <p class="text-xs text-neutral-500 mt-1 line-clamp-2"><?= e($cat['tagline']) ?></p>
        <p class="text-[11px] text-neutral-400 mt-2"><?= (int) $cat['product_count'] ?> product(s) in this category</p>
        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-neutral-100">
          <a href="<?= url('/admin/categories/' . (int) $cat['id'] . '/edit') ?>" class="text-xs font-bold text-black hover:underline">Edit</a>
          <?php if ($cat['image']): ?>
            <form method="post" action="<?= url('/admin/categories/' . (int) $cat['id'] . '/delete-image') ?>" onsubmit="return confirm('Remove the cover image for this category?');">
              <?= csrfField() ?>
              <button type="submit" class="text-xs font-bold text-rose-600 hover:underline cursor-pointer">Remove Image</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
