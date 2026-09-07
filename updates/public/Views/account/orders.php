<?php
/** Requires $customer, $orders, $itemsByOrder in scope. */
require __DIR__ . '/layout-header.php';

$statusStyles = [
    'pending' => 'bg-neutral-100 text-black',
    'processing' => 'bg-blue-100 text-blue-800',
    'shipped' => 'bg-neutral-100 text-black',
    'delivered' => 'bg-black text-white',
    'cancelled' => 'bg-rose-100 text-rose-700',
];
?>

<div class="mb-8">
  <span class="text-xs font-bold text-black uppercase tracking-widest block mb-1">My Account</span>
  <h1 class="font-serif-heading text-3xl font-bold text-[#0a0a0a]">Your Orders</h1>
  <p class="text-sm text-neutral-500 mt-2">
    Signed in as <strong class="text-neutral-800"><?= e($customer['email'] ?: $customer['phone']) ?></strong>
  </p>
</div>

<?php if (!$orders): ?>
  <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-10 text-center">
    <p class="font-serif-heading text-lg font-bold text-neutral-900 mb-2">No orders yet</p>
    <p class="text-sm text-neutral-500 mb-6">Once you place an order, it will show up here for tracking.</p>
    <a href="<?= url('/') ?>" class="inline-block bg-[#0a0a0a] text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest hover:bg-black transition-colors border border-neutral-300">Start Shopping</a>
  </div>
<?php endif; ?>

<div class="space-y-5">
  <?php foreach ($orders as $order): ?>
    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-neutral-100 flex flex-wrap items-center justify-between gap-2">
        <div>
          <p class="font-mono font-bold text-sm text-neutral-900"><?= e($order['order_ref']) ?></p>
          <p class="text-[11px] text-neutral-400"><?= e(date('M j, Y g:ia', strtotime($order['created_at']))) ?></p>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $statusStyles[$order['status']] ?? 'bg-neutral-100 text-neutral-700' ?>">
          <?= e($order['status']) ?>
        </span>
      </div>
      <div class="p-5 space-y-3">
        <?php foreach ($itemsByOrder[$order['id']] ?? [] as $item): ?>
          <div class="flex items-center gap-3">
            <?php if ($item['product_image']): ?>
              <img src="<?= e(imageUrl($item['product_image'])) ?>" alt="" class="w-12 h-14 object-cover rounded-md border border-neutral-200" />
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-neutral-900 truncate"><?= e($item['product_name']) ?></p>
              <p class="text-[11px] text-neutral-500"><?= e($item['color_name']) ?> &bull; Size <?= e($item['size_label']) ?> &bull; Qty <?= (int) $item['quantity'] ?></p>
            </div>
            <p class="text-sm font-bold text-[#8b1c1c]"><?= e(formatPrice((float) $item['unit_price'] * $item['quantity'], $order['currency'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="px-5 py-3 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between text-sm">
        <span class="text-neutral-500">Total</span>
        <span class="font-bold text-[#0a0a0a]"><?= e(formatPrice((float) $order['total'], $order['currency'])) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
