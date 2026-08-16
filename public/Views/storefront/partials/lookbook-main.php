<?php
/**
 * Requires $activeLook in scope. Re-used by app.js's client-side re-render
 * (see renderLookbookMain in assets/js/app.js) when switching tabs.
 */
?>
<img src="<?= e(imageUrl($activeLook['mainImage'] ?? '')) ?>" alt="<?= e($activeLook['title'] ?? '') ?>" class="w-full h-full object-cover object-center filter brightness-95" />
<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

<?php foreach ($activeLook['products'] as $item): ?>
  <div class="absolute transform -translate-x-1/2 -translate-y-1/2 group z-20 cursor-pointer" style="left: <?= (float) $item['xPercent'] ?>%; top: <?= (float) $item['yPercent'] ?>%;" data-quickview-trigger data-product-id="<?= e($item['product']['id']) ?>">
    <div class="relative">
      <div class="w-7 h-7 bg-white/90 text-black rounded-full flex items-center justify-center font-bold text-xs shadow-xl animate-pulse">+</div>
      <div class="absolute left-full top-1/2 -translate-y-1/2 ml-3 hidden group-hover:flex items-center bg-white text-black p-2.5 rounded-xs shadow-2xl w-52 z-30 border border-neutral-200 animate-fade-in">
        <img src="<?= e(imageUrl($item['product']['images'][0] ?? '')) ?>" alt="<?= e($item['product']['name'] ?? '') ?>" class="w-10 h-12 object-cover rounded-xs mr-2" />
        <div class="flex-1 min-w-0">
          <p class="font-serif-heading font-bold text-xs text-neutral-900 truncate"><?= e($item['product']['name'] ?? '') ?></p>
          <p class="text-[10px] text-neutral-800 font-bold mt-0.5">$<?= e($item['product']['price'] ?? '') ?></p>
          <span class="text-[9px] uppercase tracking-wider text-neutral-400 font-semibold block mt-0.5">Shop This Piece →</span>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<div class="absolute bottom-6 left-6 max-w-lg text-left">
  <span class="text-xs font-mono text-white tracking-widest uppercase block mb-1"><?= e($activeLook['subtitle']) ?></span>
  <h3 class="font-serif-heading text-2xl sm:text-3xl font-bold text-white"><?= e($activeLook['title']) ?></h3>
</div>
