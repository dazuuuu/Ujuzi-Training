<?php
/** Requires $orders, $statuses, $statusFilter in scope. */
require __DIR__ . '/../layout-header.php';
?>

<div class="flex items-center gap-2 mb-5 text-xs font-semibold">
  <a href="<?= url('/admin/orders') ?>" class="px-3 py-1.5 rounded-full <?= !$statusFilter ? 'bg-white text-black' : 'bg-white border border-neutral-300 text-neutral-600' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= url('/admin/orders?status=' . $s) ?>" class="px-3 py-1.5 rounded-full <?= $statusFilter === $s ? 'bg-white text-black' : 'bg-white border border-neutral-300 text-neutral-600' ?>"><?= ucfirst($s) ?></a>
  <?php endforeach; ?>
</div>

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-left text-xs">
    <thead class="bg-neutral-50 text-neutral-500 uppercase tracking-wider text-[10px]">
      <tr>
        <th class="px-5 py-3">Order Ref</th>
        <th class="px-5 py-3">Customer</th>
        <th class="px-5 py-3">Total</th>
        <th class="px-5 py-3">Status</th>
        <th class="px-5 py-3">Date</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-100">
      <?php if (!$orders): ?>
        <tr><td colspan="5" class="px-5 py-8 text-center text-neutral-400">No orders found.</td></tr>
      <?php endif; ?>
      <?php foreach ($orders as $o): ?>
        <tr class="hover:bg-neutral-50 cursor-pointer" onclick="location.href='<?= url('/admin/orders/' . urlencode($o['order_ref'])) ?>'">
          <td class="px-5 py-3 font-mono font-bold text-black"><?= e($o['order_ref']) ?></td>
          <td class="px-5 py-3"><?= e(trim($o['first_name'] . ' ' . $o['last_name'])) ?: e($o['email'] ?: $o['phone']) ?></td>
          <td class="px-5 py-3 font-bold text-[#8b1c1c]"><?= e(formatPrice((float) $o['total'], $o['currency'])) ?></td>
          <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-neutral-100 text-neutral-700"><?= e($o['status']) ?></span></td>
          <td class="px-5 py-3 text-neutral-500"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout-footer.php'; ?>
