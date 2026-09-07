<?php
/** Requires $product (nullable), $errors, $form, $existingImages, $categories in scope. */
require __DIR__ . '/../layout-header.php';
$actionUrl = $product ? url('/admin/products/' . (int) $product['id']) : url('/admin/products');
$selectedCategories = $form['category_keys'] ?? [];
$colors = is_array($form['colors'] ?? null) ? $form['colors'] : [];
$hasOffer = !empty($form['has_offer']);
?>

<form method="post" action="<?= $actionUrl ?>" enctype="multipart/form-data" class="max-w-4xl space-y-6" id="product-form">
  <?= csrfField() ?>

  <?php foreach ($errors as $err): ?>
    <div class="bg-rose-50 border border-rose-300 text-rose-800 text-sm font-semibold rounded-lg p-3"><?= e($err) ?></div>
  <?php endforeach; ?>

  <?php if ($product): ?>
    <div class="text-xs text-neutral-400 font-mono">Product code: <?= e($product['product_code']) ?></div>
  <?php endif; ?>

  <!-- Basic details -->
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Product Details</h2>

    <div class="grid grid-cols-2 gap-4">
      <div class="col-span-2">
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Product Name</label>
        <input type="text" name="name" required value="<?= e($form['name']) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
      </div>
      <div class="col-span-2">
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Description <span class="text-neutral-400 font-normal normal-case">(optional)</span></label>
        <textarea name="description" rows="3" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black"><?= e($form['description']) ?></textarea>
      </div>
      <div class="col-span-2">
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Categories</label>
        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
          <?php foreach ($categories as $cat): ?>
            <label class="flex items-center gap-2 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm text-neutral-700 cursor-pointer hover:border-black">
              <input type="checkbox" name="category_keys[]" value="<?= e($cat['category_key']) ?>" <?= in_array($cat['category_key'], $selectedCategories, true) ? 'checked' : '' ?> class="w-4 h-4 accent-black" />
              <span><?= e($cat['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="text-[11px] text-neutral-400 mt-1">Select one or more categories. The first selected category is used as the primary category.</p>
      </div>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Base Price (USD base unit)</label>
        <input type="number" step="0.01" min="0.01" name="base_price" required value="<?= e($form['base_price'] ?? $form['price']) ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
      </div>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Sizes <span class="text-neutral-400 font-normal normal-case">(optional, one per line)</span></label>
        <textarea name="sizes" rows="3" placeholder="S&#10;M&#10;L" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm font-mono focus:outline-none focus:border-black"><?= e($form['sizes']) ?></textarea>
      </div>
    </div>
  </div>

  <!-- Offer -->
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
    <label class="flex items-center gap-2 text-sm font-bold text-neutral-800">
      <input type="checkbox" name="has_offer" value="1" <?= $hasOffer ? 'checked' : '' ?> class="w-4 h-4 accent-black" data-offer-toggle />
      <span>Has an offer <span class="text-neutral-400 font-normal">(optional)</span></span>
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 <?= $hasOffer ? '' : 'hidden' ?>" data-offer-fields>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Offer Type</label>
        <select name="offer_type" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black">
          <option value="price" <?= ($form['offer_type'] ?? 'price') === 'price' ? 'selected' : '' ?>>Offer price</option>
          <option value="percentage" <?= ($form['offer_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage off</option>
        </select>
      </div>
      <div class="col-span-2">
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Offer Price / Percentage</label>
        <input type="number" step="0.01" min="0" name="offer_value" value="<?= e($form['offer_value'] ?? '') ?>" class="w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
      </div>
    </div>
  </div>

  <!-- Photos -->
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] border-b border-neutral-100 pb-3">Product Photos</h2>
    <?php if ($existingImages): ?>
      <div>
        <label class="text-[11px] font-bold text-neutral-600 uppercase">Current Cover</label>
        <img src="<?= e(imageUrl($existingImages[0])) ?>" alt="" class="mt-2 w-32 aspect-square object-cover rounded-lg border border-neutral-200" />
      </div>
      <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
        <?php foreach ($existingImages as $idx => $img): ?>
          <label class="relative block">
            <img src="<?= e(imageUrl($img)) ?>" alt="" class="w-full aspect-square object-cover rounded-lg border border-neutral-200" />
            <?php if ($idx === 0): ?>
              <span class="absolute bottom-1 left-1 bg-black/80 text-white text-[9px] font-bold rounded px-1.5 py-0.5 uppercase">Cover</span>
            <?php endif; ?>
            <span class="absolute top-1 right-1 bg-white/90 rounded p-1 shadow">
              <input type="checkbox" name="images_remove[]" value="<?= e($img) ?>" class="w-3.5 h-3.5 accent-rose-600" title="Remove this photo" />
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="text-[11px] text-neutral-400">Tick a photo's checkbox to remove it when you save.</p>
    <?php endif; ?>
    <div>
      <label class="text-[11px] font-bold text-neutral-600 uppercase">Cover Image</label>
      <input type="file" name="cover_image" accept="image/*" <?= $existingImages ? '' : 'required' ?> class="w-full mt-1 text-sm" />
      <p class="text-[11px] text-neutral-400 mt-1">This is the main thumbnail shown on the frontpage. JPG, PNG, WEBP or GIF, up to 8MB.</p>
    </div>
    <div>
      <label class="text-[11px] font-bold text-neutral-600 uppercase">Gallery Images <span class="text-neutral-400 font-normal normal-case">(optional, upload more than one)</span></label>
      <input type="file" name="gallery_images[]" accept="image/*" multiple class="w-full mt-1 text-sm" />
    </div>
  </div>

  <!-- Colors -->
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 pb-3">
      <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">Colors <span class="text-neutral-400 font-normal text-sm">(optional)</span></h2>
      <button type="button" data-add-color class="bg-white hover:bg-neutral-50 border border-neutral-300 text-neutral-900 text-xs font-bold px-3 py-2 rounded-lg uppercase tracking-widest">Add Color</button>
    </div>
    <div class="space-y-3" data-color-list>
      <?php foreach ($colors as $color): ?>
        <div class="grid grid-cols-[auto_1fr_auto] gap-3 items-center" data-color-row>
          <input type="color" name="color_hexes[]" value="<?= e($color['hex'] ?? '#000000') ?>" class="w-11 h-10 p-1 bg-white border border-neutral-300 rounded-lg" />
          <input type="text" name="color_names[]" value="<?= e($color['name'] ?? '') ?>" placeholder="Color name" class="w-full bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />
          <button type="button" data-remove-color class="text-xs font-bold text-rose-600 hover:text-rose-700">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="text-[11px] text-neutral-400">Add rows only when the product comes in multiple colors.</p>
  </div>

  <div class="flex items-center gap-3">
    <button type="submit" class="bg-[#0a0a0a] hover:bg-black text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest transition-colors cursor-pointer border border-neutral-300">
      <?= $product ? 'Save Changes' : 'Create Product' ?>
    </button>
    <a href="<?= url('/admin/products') ?>" class="text-xs font-bold text-neutral-500 hover:text-neutral-800">Cancel</a>
  </div>
</form>

<script>
  (function () {
    var offerToggle = document.querySelector('[data-offer-toggle]');
    var offerFields = document.querySelector('[data-offer-fields]');
    if (offerToggle && offerFields) {
      offerToggle.addEventListener('change', function () {
        offerFields.classList.toggle('hidden', !offerToggle.checked);
      });
    }

    var list = document.querySelector('[data-color-list]');
    var add = document.querySelector('[data-add-color]');
    function bindRemove(row) {
      var button = row.querySelector('[data-remove-color]');
      if (button) {
        button.addEventListener('click', function () {
          row.remove();
        });
      }
    }
    if (list) {
      list.querySelectorAll('[data-color-row]').forEach(bindRemove);
    }
    if (add && list) {
      add.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'grid grid-cols-[auto_1fr_auto] gap-3 items-center';
        row.setAttribute('data-color-row', '');
        row.innerHTML = '<input type="color" name="color_hexes[]" value="#000000" class="w-11 h-10 p-1 bg-white border border-neutral-300 rounded-lg" />' +
          '<input type="text" name="color_names[]" placeholder="Color name" class="w-full bg-white border border-neutral-300 rounded-lg p-2.5 text-sm focus:outline-none focus:border-black" />' +
          '<button type="button" data-remove-color class="text-xs font-bold text-rose-600 hover:text-rose-700">Remove</button>';
        list.appendChild(row);
        bindRemove(row);
      });
    }
  })();
</script>

<?php require __DIR__ . '/../layout-footer.php'; ?>
