<?php
/** Requires $activeLook in scope. */
foreach ($activeLook['products'] as $item):
    $p = $item['product'];
?>
  <div data-quickview-trigger data-product-id="<?= e($p['id']) ?>" class="flex items-center space-x-3 p-3 bg-neutral-900/90 border border-neutral-800 rounded-xs hover:border-neutral-300 transition-colors cursor-pointer group">
    <img src="<?= e(imageUrl($p['images'][0] ?? '')) ?>" alt="<?= e($p['name'] ?? '') ?>" class="w-16 h-20 object-cover rounded-xs" />
    <div class="flex-1 min-w-0">
      <p class="text-[10px] text-white font-mono uppercase tracking-widest"><?= e($p['subCategory'] ?? '') ?></p>
      <h5 class="font-serif-heading text-sm font-bold text-white group-hover:text-neutral-200 truncate"><?= e($p['name'] ?? '') ?></h5>
      <p class="text-xs font-bold text-neutral-300 mt-1">$<?= e($p['price'] ?? '') ?></p>
    </div>
    <div class="p-2 bg-neutral-800 group-hover:bg-neutral-200 group-hover:text-black rounded-xs transition-colors">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
    </div>
  </div>
<?php endforeach; ?>
