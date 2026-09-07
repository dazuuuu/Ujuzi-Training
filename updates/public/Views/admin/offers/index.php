<?php
/** Requires $onOffer, $notOnOffer in scope. */
require __DIR__ . '/../layout-header.php';
?>

<p class="text-sm text-neutral-700 font-medium mb-5">Offers are attached to existing products. Add an optional offer banner image and expiry time; matured offers are removed from the storefront automatically.</p>

<div class="bg-white border border-neutral-300 rounded-xl shadow-sm overflow-x-auto mb-8">
  <div class="px-5 py-4 border-b border-neutral-200">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">Currently On Offer (<?= count($onOffer) ?>)</h2>
  </div>
  <table class="w-full min-w-[980px] text-left text-sm admin-data-table">
    <thead class="bg-neutral-100 text-black uppercase tracking-wider text-[11px]">
      <tr>
        <th class="px-5 py-3">Product</th>
        <th class="px-5 py-3">Offer Price</th>
        <th class="px-5 py-3">Original Price</th>
        <th class="px-5 py-3">Offer Image</th>
        <th class="px-5 py-3">Ends At</th>
        <th class="px-5 py-3">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-100">
      <?php if (!$onOffer): ?>
        <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-700 font-medium">No products on offer right now.</td></tr>
      <?php endif; ?>
      <?php foreach ($onOffer as $p):
        $images = json_decode($p['images'] ?? '[]', true) ?: [];
        $offerImage = $p['offer_image'] ?? null;
        $endsValue = !empty($p['offer_ends_at']) ? date('Y-m-d\TH:i', strtotime($p['offer_ends_at'])) : '';
        $formId = 'offer-form-' . (int) $p['id'];
      ?>
        <tr class="hover:bg-neutral-50">
          <td class="px-5 py-3 flex items-center gap-3">
            <?php if ($offerImage || $images): ?><img src="<?= e(imageUrl($offerImage ?: $images[0])) ?>" class="w-10 h-12 object-cover rounded-md border border-neutral-200" alt="" /><?php endif; ?>
            <div>
              <p class="font-bold text-neutral-900"><?= e($p['name']) ?></p>
              <p class="text-[11px] text-neutral-600 font-mono font-semibold"><?= e($p['product_code']) ?></p>
            </div>
          </td>
          <td class="px-5 py-3"><input form="<?= $formId ?>" type="number" step="0.01" name="price" value="<?= e($p['price']) ?>" class="w-28 border border-neutral-400 rounded-lg p-2 text-sm font-semibold focus:outline-none focus:border-black" /></td>
          <td class="px-5 py-3"><input form="<?= $formId ?>" type="number" step="0.01" name="original_price" value="<?= e($p['original_price']) ?>" class="w-28 border border-neutral-400 rounded-lg p-2 text-sm font-semibold focus:outline-none focus:border-black" /></td>
          <td class="px-5 py-3">
            <input form="<?= $formId ?>" type="file" name="offer_image" accept="image/*" class="block w-44 text-xs text-neutral-800 font-medium" />
            <?php if ($offerImage): ?>
              <label class="mt-1 flex items-center gap-1 text-[11px] text-neutral-700 font-medium"><input form="<?= $formId ?>" type="checkbox" name="remove_offer_image" value="1" class="accent-black" /> Remove current</label>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3"><input form="<?= $formId ?>" type="datetime-local" name="offer_ends_at" value="<?= e($endsValue) ?>" class="w-44 border border-neutral-400 rounded-lg p-2 text-sm font-semibold focus:outline-none focus:border-black" /></td>
          <td class="px-5 py-3 flex items-center gap-3">
            <form id="<?= $formId ?>" method="post" enctype="multipart/form-data" action="<?= url('/admin/offers/' . (int) $p['id'] . '/update') ?>" class="contents">
              <?= csrfField() ?>
              <button type="submit" class="font-bold text-black hover:underline cursor-pointer">Save</button>
            </form>
            <form method="post" action="<?= url('/admin/offers/' . (int) $p['id'] . '/remove') ?>" onsubmit="return confirm('Remove this product from offers?');" class="contents">
              <?= csrfField() ?>
              <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="bg-white border border-neutral-300 rounded-xl shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-neutral-200">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a]">Add a Product to Offers</h2>
  </div>
  <table class="w-full text-left text-sm admin-data-table">
    <tbody class="divide-y divide-neutral-100">
      <?php if (!$notOnOffer): ?>
        <tr><td class="px-5 py-8 text-center text-neutral-700 font-medium">Every product is already on offer.</td></tr>
      <?php endif; ?>
      <?php foreach ($notOnOffer as $p): ?>
        <tr class="hover:bg-neutral-50">
          <td class="px-5 py-3">
            <p class="font-bold text-neutral-900"><?= e($p['name']) ?></p>
            <p class="text-[11px] text-neutral-600 font-mono font-semibold"><?= e($p['product_code']) ?></p>
          </td>
          <td class="px-5 py-3 text-neutral-900 font-bold"><?= e(formatPrice((float) $p['price'], 'KSH')) ?></td>
          <td class="px-5 py-3 text-right">
            <form method="post" action="<?= url('/admin/offers/' . (int) $p['id'] . '/add') ?>">
              <?= csrfField() ?>
              <button type="submit" class="font-bold text-black hover:underline cursor-pointer">+ Add to Offers</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
