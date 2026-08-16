<?php
/** Requires $products, $search in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="flex items-center justify-between mb-5 gap-3">
  <form method="get" action="<?= url('/admin/products') ?>" class="flex-1 max-w-xs">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products..." class="w-full bg-white border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-black" />
  </form>
  <a href="<?= url('/admin/products/create') ?>" class="bg-[#0a0a0a] text-white text-xs font-bold px-4 py-2.5 rounded-lg uppercase tracking-widest hover:bg-black transition-colors border border-neutral-300 whitespace-nowrap">+ Add Product</a>
</div>

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-left text-xs">
    <thead class="bg-neutral-50 text-neutral-500 uppercase tracking-wider text-[10px]">
      <tr>
        <th class="px-5 py-3">Photo</th>
        <th class="px-5 py-3">Name</th>
        <th class="px-5 py-3">Category</th>
        <th class="px-5 py-3">Price</th>
        <th class="px-5 py-3">Flags</th>
        <th class="px-5 py-3">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-100">
      <?php if (!$products): ?>
        <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-400">No products found.</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $p):
        $images = json_decode($p['images'] ?? '[]', true) ?: [];
        $colors = json_decode($p['colors'] ?? '[]', true) ?: [];
        $categoryKeys = json_decode($p['category_keys'] ?? '[]', true) ?: [$p['category_key']];
      ?>
        <tr class="hover:bg-neutral-50">
          <td class="px-5 py-3">
            <?php if ($images): ?>
              <img src="<?= e(imageUrl($images[0])) ?>" alt="" class="w-12 h-14 object-cover rounded-md border border-neutral-200" />
            <?php else: ?>
              <div class="w-12 h-14 bg-neutral-100 rounded-md"></div>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3">
            <p class="font-bold text-neutral-900"><?= e($p['name']) ?></p>
            <p class="text-[10px] text-neutral-400 font-mono"><?= e($p['product_code']) ?></p>
            <div class="flex gap-1 mt-1">
              <?php foreach ($colors as $c): ?>
                <span class="w-3.5 h-3.5 rounded-full border border-neutral-300" style="background-color:<?= e($c['hex']) ?>" title="<?= e($c['name']) ?>"></span>
              <?php endforeach; ?>
            </div>
          </td>
          <td class="px-5 py-3 text-neutral-600"><?= e(implode(', ', $categoryKeys)) ?></td>
          <td class="px-5 py-3">
            <span class="font-bold text-[#8b1c1c]"><?= e(formatPrice((float) $p['price'], 'KSH')) ?></span>
            <?php if ($p['original_price']): ?>
              <span class="text-neutral-400 line-through ml-1"><?= e(formatPrice((float) $p['original_price'], 'KSH')) ?></span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3">
            <div class="flex flex-wrap gap-1">
              <?php if ($p['is_new']): ?><span class="px-1.5 py-0.5 rounded bg-neutral-100 text-black text-[9px] font-bold">NEW</span><?php endif; ?>
              <?php if ($p['is_best_seller']): ?><span class="px-1.5 py-0.5 rounded bg-neutral-100 text-black text-[9px] font-bold">BEST</span><?php endif; ?>
              <?php if ($p['is_sale']): ?><span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[9px] font-bold">SALE</span><?php endif; ?>
              <?php if (!$p['in_stock']): ?><span class="px-1.5 py-0.5 rounded bg-neutral-200 text-neutral-600 text-[9px] font-bold">OUT</span><?php endif; ?>
            </div>
          </td>
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <a href="<?= url('/admin/products/' . (int) $p['id'] . '/edit') ?>" class="font-bold text-black hover:underline">Edit</a>
              <form method="post" action="<?= url('/admin/products/' . (int) $p['id'] . '/delete') ?>" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                <?= csrfField() ?>
                <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
