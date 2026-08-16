<?php
/** Requires $pageKey, $label, $fallbackTitle, $fallbackDescription, $featuredImage, $errors, $form in scope. */
require __DIR__ . '/../layout-header.php';
?>

<a href="<?= url('/admin/seo') ?>" class="text-xs font-bold text-black hover:underline">&larr; Back to SEO pages</a>

<form method="post" action="<?= url('/admin/seo/' . rawurlencode($pageKey)) ?>" enctype="multipart/form-data" class="max-w-2xl bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5 mt-4">
  <?= csrfField() ?>

  <?php foreach ($errors as $err): ?>
    <div class="bg-rose-50 border border-rose-300 text-rose-800 text-sm font-semibold rounded-lg p-3"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div>
    <p class="text-[11px] font-bold text-neutral-500 uppercase tracking-wider">Editing SEO for</p>
    <p class="font-serif-heading text-lg font-bold text-[#0a0a0a]"><?= e($label) ?></p>
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Meta Title</label>
    <input type="text" name="meta_title" value="<?= e($form['meta_title']) ?>" placeholder="<?= e($fallbackTitle) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
    <p class="text-[11px] text-neutral-400 mt-1">Leave blank to use the auto-generated title above as a placeholder.</p>
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Meta Description</label>
    <textarea name="meta_description" rows="3" placeholder="<?= e($fallbackDescription) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black"><?= e($form['meta_description']) ?></textarea>
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Meta Keywords <span class="text-neutral-400 font-normal normal-case">(comma-separated)</span></label>
    <input type="text" name="meta_keywords" value="<?= e($form['meta_keywords']) ?>" placeholder="luxury furniture, dining table, Nairobi" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Tags <span class="text-neutral-400 font-normal normal-case">(comma-separated)</span></label>
    <input type="text" name="tags" value="<?= e($form['tags']) ?>" placeholder="furniture, gold, dining" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
  </div>

  <div>
    <label class="text-[11px] font-bold text-neutral-600 uppercase">Featured Image (og:image)</label>
    <?php if ($featuredImage): ?>
      <img src="<?= e(imageUrl($featuredImage)) ?>" alt="" class="w-40 h-28 object-cover rounded-lg border border-neutral-200 mt-2 mb-2" />
    <?php endif; ?>
    <input type="file" name="featured_image" accept="image/*" class="w-full mt-1 text-sm" />
    <p class="text-[11px] text-neutral-400 mt-1">Used for social share previews. Leave empty to keep the current image.</p>
  </div>

  <div class="flex items-center gap-3 pt-3 border-t border-neutral-100">
    <button type="submit" class="bg-[#0a0a0a] hover:bg-black text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest transition-colors cursor-pointer border border-neutral-300">Save SEO Details</button>
    <a href="<?= url('/admin/seo') ?>" class="text-xs font-bold text-neutral-500 hover:text-neutral-800">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/../layout-footer.php'; ?>
