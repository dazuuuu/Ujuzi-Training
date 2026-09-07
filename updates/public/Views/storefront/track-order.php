<?php
$statusLabels = [
    'pending' => 'Order received',
    'processing' => 'Preparing your order',
    'shipped' => 'Out for delivery',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];
$statusSteps = ['pending', 'processing', 'shipped', 'delivered'];
$currentStatus = $order['status'] ?? null;
$currentIndex = $currentStatus ? array_search($currentStatus, $statusSteps, true) : false;
?>
<main class="flex-1 bg-white">
  <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    <div class="mb-8">
      <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-black p-2 text-white">
        <?= storeLogoHtml('h-full w-full object-contain rounded-lg', 'w-7 h-7 text-white') ?>
      </div>
      <span class="text-xs font-bold text-neutral-500 uppercase tracking-widest block mb-1">Customer Care</span>
      <h1 class="font-serif-heading text-3xl sm:text-4xl font-bold text-black">Track My Order</h1>
      <p class="text-sm text-neutral-600 mt-2">Enter your order reference to see the latest status and delivery details.</p>
    </div>

    <form method="post" action="<?= url('/track-order') ?>" class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row gap-3">
      <?= csrfField() ?>
      <input type="text" name="order_ref" value="<?= e($orderRef) ?>" placeholder="Order reference e.g. PENT-123456" class="flex-1 bg-white border border-neutral-300 rounded-lg px-4 py-3 text-sm uppercase font-mono focus:outline-none focus:border-black" />
      <button type="submit" class="bg-black text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest hover:bg-neutral-900 transition-colors">Track Order</button>
    </form>

    <?php if ($searched && !$order): ?>
      <div class="mt-6 border border-neutral-200 rounded-xl p-6 text-sm text-neutral-600">
        No order was found for <span class="font-mono font-bold text-black"><?= e($orderRef) ?></span>. Please check the reference and try again.
      </div>
    <?php endif; ?>

    <?php if ($order): ?>
      <div class="mt-8 bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <p class="text-xs text-neutral-500 uppercase tracking-widest font-bold">Order <?= e($order['order_ref']) ?></p>
            <h2 class="text-xl font-bold text-black mt-1"><?= e($statusLabels[$order['status']] ?? ucfirst($order['status'])) ?></h2>
          </div>
          <span class="inline-flex w-fit px-3 py-1 rounded-full bg-black text-white text-[11px] uppercase tracking-widest font-bold"><?= e($order['status']) ?></span>
        </div>

        <?php if ($currentIndex !== false): ?>
          <div class="p-5 sm:p-6 border-b border-neutral-200">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <?php foreach ($statusSteps as $idx => $step): ?>
                <div class="border <?= $idx <= $currentIndex ? 'border-black bg-black text-white' : 'border-neutral-200 bg-white text-neutral-500' ?> rounded-lg p-3">
                  <p class="text-[10px] uppercase tracking-widest font-bold">Step <?= $idx + 1 ?></p>
                  <p class="text-xs font-semibold mt-1"><?= e($statusLabels[$step]) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="p-5 sm:p-6 grid sm:grid-cols-2 gap-6 text-sm">
          <div>
            <h3 class="font-bold text-black mb-2">Delivery</h3>
            <p class="text-neutral-600"><?= e($order['shipping_address']) ?></p>
            <p class="text-neutral-600"><?= e(trim(($order['shipping_city'] ?? '') . ' ' . ($order['shipping_postal_code'] ?? ''))) ?></p>
            <p class="text-neutral-600"><?= e($order['shipping_country']) ?></p>
          </div>
          <div>
            <h3 class="font-bold text-black mb-2">Summary</h3>
            <div class="space-y-1 text-neutral-600">
              <div class="flex justify-between"><span>Subtotal</span><span><?= e(formatPrice((float) $order['subtotal'], $order['currency'])) ?></span></div>
              <div class="flex justify-between"><span>Discount</span><span><?= e(formatPrice((float) $order['discount'], $order['currency'])) ?></span></div>
              <div class="flex justify-between"><span>Shipping</span><span><?= e(formatPrice((float) $order['shipping'], $order['currency'])) ?></span></div>
              <div class="flex justify-between pt-2 border-t border-neutral-200 font-bold text-black"><span>Total</span><span><?= e(formatPrice((float) $order['total'], $order['currency'])) ?></span></div>
            </div>
          </div>
        </div>

        <div class="px-5 sm:px-6 pb-6">
          <h3 class="font-bold text-black mb-3">Items</h3>
          <div class="divide-y divide-neutral-100 border border-neutral-200 rounded-lg overflow-hidden">
            <?php foreach ($items as $item): ?>
              <div class="p-3 flex items-center gap-3">
                <?php if (!empty($item['product_image'])): ?><img src="<?= e(imageUrl($item['product_image'])) ?>" class="w-14 h-16 object-cover rounded border border-neutral-200" alt="" /><?php endif; ?>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-black"><?= e($item['product_name']) ?></p>
                  <p class="text-xs text-neutral-500"><?= e($item['color_name']) ?><?= $item['size_label'] ? ' / ' . e($item['size_label']) : '' ?> · Qty <?= (int) $item['quantity'] ?></p>
                </div>
                <p class="text-sm font-bold text-black"><?= e(formatPrice((float) $item['unit_price'] * (int) $item['quantity'], $order['currency'])) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </section>
</main>
