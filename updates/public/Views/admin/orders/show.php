<?php
/** Requires $order, $items, $statuses in scope. */
require __DIR__ . '/../layout-header.php';
?>
<a href="<?= url('/admin/orders') ?>" class="text-xs font-bold text-black hover:underline">&larr; Back to all orders</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
  <div class="lg:col-span-2 bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
    <h2 class="font-serif-heading text-lg font-bold text-[#0a0a0a] mb-4">Items</h2>
    <div class="space-y-3">
      <?php foreach ($items as $it): ?>
        <div class="flex gap-3 items-center border-b border-neutral-100 pb-3">
          <?php if ($it['product_image']): ?><img src="<?= e(imageUrl($it['product_image'])) ?>" class="w-14 h-16 object-cover rounded-md border border-neutral-200" alt="" /><?php endif; ?>
          <div class="flex-1">
            <p class="font-bold text-sm text-neutral-900"><?= e($it['product_name']) ?></p>
            <p class="text-xs text-neutral-500"><?= e($it['color_name']) ?> &bull; Size <?= e($it['size_label']) ?> &bull; Qty <?= (int) $it['quantity'] ?></p>
          </div>
          <p class="font-bold text-[#8b1c1c] text-sm"><?= e(formatPrice((float) $it['unit_price'] * $it['quantity'], $order['currency'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 space-y-1.5 text-sm text-neutral-600">
      <div class="flex justify-between"><span>Subtotal</span><span class="font-semibold"><?= e(formatPrice((float) $order['subtotal'], $order['currency'])) ?></span></div>
      <?php if ($order['discount'] > 0): ?><div class="flex justify-between text-black"><span>Discount</span><span>-<?= e(formatPrice((float) $order['discount'], $order['currency'])) ?></span></div><?php endif; ?>
      <div class="flex justify-between"><span>Shipping</span><span class="font-semibold"><?= $order['shipping'] > 0 ? e(formatPrice((float) $order['shipping'], $order['currency'])) : 'FREE' ?></span></div>
      <div class="flex justify-between text-base font-bold text-[#0a0a0a] pt-2 border-t border-neutral-200"><span>Total</span><span class="text-[#8b1c1c]"><?= e(formatPrice((float) $order['total'], $order['currency'])) ?></span></div>
    </div>
  </div>

  <div class="space-y-4">
    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
      <h3 class="font-serif-heading font-bold text-[#0a0a0a] mb-3">Status</h3>
      <form method="post" action="<?= url('/admin/orders/' . (int) $order['id'] . '/status') ?>">
        <?= csrfField() ?>
        <input type="hidden" name="order_ref" value="<?= e($order['order_ref']) ?>" />
        <select name="status" onchange="this.form.submit()" class="w-full border border-neutral-300 rounded-lg p-2 text-sm">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 text-sm space-y-2">
      <h3 class="font-serif-heading font-bold text-[#0a0a0a] mb-1">Customer</h3>
      <p class="text-neutral-800"><?= e(trim($order['cust_first'] . ' ' . $order['cust_last'])) ?></p>
      <?php if ($order['email']): ?><p class="text-neutral-500"><?= e($order['email']) ?></p><?php endif; ?>
      <?php if ($order['phone']): ?><p class="text-neutral-500"><?= e($order['phone']) ?></p><?php endif; ?>
      <p class="text-neutral-500 pt-2 border-t border-neutral-100 mt-2">
        <?= e($order['shipping_address']) ?><br>
        <?= e($order['shipping_city']) ?>, <?= e($order['shipping_postal_code']) ?><br>
        <?= e($order['shipping_country']) ?>
      </p>
      <p class="text-neutral-500 pt-2 border-t border-neutral-100 mt-2">Payment: <?= e($order['payment_method']) ?></p>
      <p class="text-neutral-400 text-xs">Placed <?= e(date('M j, Y g:ia', strtotime($order['created_at']))) ?></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
