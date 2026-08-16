<?php
/** Requires $stats, $recentOrders, $trendRows, $categoryBreakdown, $offerRows in scope. */
require __DIR__ . '/layout-header.php';

$trendRows = array_values($trendRows ?? []);
$categoryBreakdown = array_values($categoryBreakdown ?? []);
$offerRows = array_values($offerRows ?? []);
$maxOrders = max(1, ...array_map(fn($row) => (int) $row['order_count'], $trendRows ?: [['order_count' => 1]]));
$maxCategoryProducts = max(1, ...array_map(fn($row) => (int) $row['product_count'], $categoryBreakdown ?: [['product_count' => 1]]));
$today = date('j M Y');
$statusTone = [
    'pending' => 'bg-neutral-100 text-neutral-900 border-neutral-300',
    'processing' => 'bg-neutral-900 text-white border-neutral-900',
    'shipped' => 'bg-neutral-200 text-black border-neutral-300',
    'delivered' => 'bg-black text-white border-black',
    'cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
];
$kpis = [
    [
        'label' => 'Total Revenue',
        'value' => formatPrice((float) $stats['totalRevenue'], 'KSH'),
        'meta' => formatPrice((float) $stats['monthlyRevenue'], 'KSH') . ' this month',
        'href' => url('/admin/orders'),
    ],
    [
        'label' => 'Total Orders',
        'value' => number_format((int) $stats['orders']),
        'meta' => number_format((int) $stats['todayOrders']) . ' today',
        'href' => url('/admin/orders'),
    ],
    [
        'label' => 'Products',
        'value' => number_format((int) $stats['products']),
        'meta' => number_format((int) $stats['activeProducts']) . ' in stock',
        'href' => url('/admin/products'),
    ],
    [
        'label' => 'Live Offers',
        'value' => number_format((int) $stats['onOffer']),
        'meta' => number_format((int) $stats['pendingOrders']) . ' pending orders',
        'href' => url('/admin/offers'),
    ],
];
?>

<div class="space-y-6">
  <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
      <p class="text-xs font-black uppercase tracking-widest text-neutral-600">Dashboard / Overview</p>
      <h2 class="mt-2 text-3xl font-black tracking-tight text-black">Welcome back</h2>
      <p class="mt-1 text-sm font-medium text-neutral-700">Here is the live status of your shop, products, offers, and orders.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <span class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-bold text-neutral-800">Daily</span>
      <span class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-bold text-neutral-800"><?= e($today) ?></span>
      <a href="<?= url('/admin/orders') ?>" class="rounded-lg bg-black px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-neutral-900">View Orders</a>
    </div>
  </section>

  <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($kpis as $card): ?>
      <a href="<?= e($card['href']) ?>" class="rounded-xl border border-neutral-300 bg-white p-5 shadow-sm transition hover:border-black hover:shadow-md">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600"><?= e($card['label']) ?></p>
            <p class="mt-3 text-2xl font-black tracking-tight text-black"><?= e($card['value']) ?></p>
            <p class="mt-2 text-xs font-bold text-neutral-700"><?= e($card['meta']) ?></p>
          </div>
          <div class="flex h-11 items-end gap-1">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <span class="block w-1.5 rounded-full bg-neutral-<?= $i % 2 === 0 ? '900' : '300' ?>" style="height: <?= 14 + ($i * 5) ?>px"></span>
            <?php endfor; ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </section>

  <section class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="flex flex-col gap-3 border-b border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Sales Activity</p>
          <h3 class="mt-1 text-xl font-black text-black"><?= e(formatPrice((float) $stats['totalRevenue'], 'KSH')) ?></h3>
        </div>
        <div class="flex items-center gap-3 text-[11px] font-bold text-neutral-700">
          <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-black"></span>Orders</span>
          <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-neutral-300"></span>Baseline</span>
        </div>
      </div>
      <div class="p-5">
        <?php if (!$trendRows): ?>
          <div class="flex min-h-[260px] items-center justify-center rounded-lg border border-dashed border-neutral-300 text-sm font-bold text-neutral-600">No order activity yet.</div>
        <?php else: ?>
          <div class="flex h-72 items-end gap-2 border-b border-l border-neutral-300 px-3 pb-3">
            <?php foreach ($trendRows as $row):
              $height = max(14, (int) round(((int) $row['order_count'] / $maxOrders) * 230));
            ?>
              <div class="flex flex-1 flex-col items-center gap-2">
                <div class="flex w-full items-end justify-center">
                  <span class="block w-full max-w-8 rounded-t bg-black" title="<?= (int) $row['order_count'] ?> orders" style="height: <?= $height ?>px"></span>
                </div>
                <span class="text-[10px] font-black uppercase text-neutral-700"><?= e($row['month_label']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="border-b border-neutral-200 px-5 py-4">
        <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Shop Breakdown</p>
        <h3 class="mt-1 text-xl font-black text-black"><?= (int) $stats['categories'] ?> Categories</h3>
      </div>
      <div class="space-y-4 p-5">
        <?php foreach (array_slice($categoryBreakdown, 0, 6) as $row):
          $width = max(6, (int) round(((int) $row['product_count'] / $maxCategoryProducts) * 100));
        ?>
          <div>
            <div class="mb-1 flex items-center justify-between gap-3">
              <p class="truncate text-sm font-black text-black"><?= e($row['name']) ?></p>
              <p class="text-xs font-bold text-neutral-700"><?= (int) $row['product_count'] ?> products</p>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-neutral-200">
              <div class="h-full rounded-full bg-black" style="width: <?= $width ?>%"></div>
            </div>
            <p class="mt-1 text-[11px] font-semibold text-neutral-600"><?= (int) $row['offer_count'] ?> live offers</p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="grid gap-5 xl:grid-cols-[minmax(320px,0.85fr)_minmax(0,1.6fr)]">
    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Offer Watch</p>
          <h3 class="mt-1 text-lg font-black text-black">Live Offers</h3>
        </div>
        <a href="<?= url('/admin/offers') ?>" class="text-xs font-black text-black hover:underline">Manage</a>
      </div>
      <div class="divide-y divide-neutral-100">
        <?php if (!$offerRows): ?>
          <p class="p-5 text-sm font-bold text-neutral-700">No active offers right now.</p>
        <?php endif; ?>
        <?php foreach ($offerRows as $offer): ?>
          <div class="p-5">
            <p class="line-clamp-1 text-sm font-black text-black"><?= e($offer['name']) ?></p>
            <p class="mt-1 font-mono text-[11px] font-bold text-neutral-600"><?= e($offer['product_code']) ?></p>
            <div class="mt-3 flex items-center justify-between gap-3 text-xs">
              <span class="font-black text-[#8b1c1c]"><?= e(formatPrice((float) $offer['price'], 'KSH')) ?></span>
              <span class="font-bold text-neutral-700"><?= $offer['offer_ends_at'] ? 'Ends ' . e(date('M j, g:ia', strtotime($offer['offer_ends_at']))) : 'No expiry set' ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rounded-xl border border-neutral-300 bg-white shadow-sm">
      <div class="flex flex-col gap-3 border-b border-neutral-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-[11px] font-black uppercase tracking-widest text-neutral-600">Recent Orders</p>
          <h3 class="mt-1 text-lg font-black text-black">Latest Customer Activity</h3>
        </div>
        <a href="<?= url('/admin/orders') ?>" class="rounded-lg border border-neutral-300 px-3 py-2 text-xs font-black text-black hover:border-black">View All</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm admin-data-table">
          <thead class="bg-neutral-100 text-black uppercase tracking-wider text-[11px]">
            <tr>
              <th class="px-5 py-3">Order Ref</th>
              <th class="px-5 py-3">Customer</th>
              <th class="px-5 py-3">Total</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-100">
            <?php if (!$recentOrders): ?>
              <tr><td colspan="5" class="px-5 py-8 text-center font-bold text-neutral-700">No orders placed yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $o):
              $customer = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: ($o['email'] ?: $o['phone']);
              $tone = $statusTone[$o['status']] ?? 'bg-neutral-100 text-neutral-900 border-neutral-300';
            ?>
              <tr>
                <td class="px-5 py-3 font-mono font-black"><a href="<?= url('/admin/orders/' . urlencode($o['order_ref'])) ?>" class="hover:underline"><?= e($o['order_ref']) ?></a></td>
                <td class="px-5 py-3 font-bold"><?= e($customer) ?></td>
                <td class="px-5 py-3 font-black text-[#8b1c1c]"><?= e(formatPrice((float) $o['total'], $o['currency'])) ?></td>
                <td class="px-5 py-3"><span class="inline-flex rounded-full border px-2 py-1 text-[10px] font-black uppercase <?= e($tone) ?>"><?= e($o['status']) ?></span></td>
                <td class="px-5 py-3 font-semibold text-neutral-700"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
