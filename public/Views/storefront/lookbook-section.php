<?php
/**
 * Mirrors src/components/LookbookSection.tsx
 * Initial render = lookbook index 0. Tab switching + hotspot interactions
 * are re-rendered client-side in assets/js/app.js (#lookbook-main / #lookbook-list).
 * Requires $lookbook in scope.
 */
if (empty($lookbook)) {
    $activeLook = null;
} else {
    $activeLook = $lookbook[0];
}
?>
<?php if ($activeLook === null): ?>
<div id="lookbook-section" class="py-20 bg-[#141414] text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-neutral-800 bg-neutral-900 p-10 text-center">
      <p class="text-sm uppercase tracking-widest text-white">Lookbook unavailable</p>
      <h2 class="font-serif-heading text-3xl sm:text-4xl font-bold text-white mt-2">Coming soon</h2>
      <p class="mt-3 text-neutral-300">We are updating our featured collections. Please check back shortly.</p>
    </div>
  </div>
</div>
<?php return; ?>
<?php endif; ?>
<div id="lookbook-section">
<section class="py-20 bg-[#141414] text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 pb-4 border-b border-neutral-800">
      <div>
        <span class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-1.5 mb-1">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
          Editorial Style Notes
        </span>
        <h2 class="font-serif-heading text-3xl sm:text-4xl font-bold tracking-tight text-white">THE PENTAGON LOOKBOOK</h2>
      </div>

      <div class="flex items-center space-x-3 mt-4 md:mt-0" id="lookbook-tabs">
        <?php foreach ($lookbook as $idx => $look): ?>
          <button data-lookbook-index="<?= $idx ?>" class="lookbook-tab px-4 py-2 text-xs font-mono tracking-widest uppercase rounded-xs transition-colors cursor-pointer <?= $idx === 0 ? 'bg-neutral-200 text-[#1a1a1a] font-bold' : 'bg-neutral-900 text-neutral-400 hover:text-white border border-neutral-800' ?>"><?= e($look['season']) ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      <div class="lg:col-span-8 relative aspect-[4/3] rounded-xs overflow-hidden bg-neutral-900 border border-neutral-800 shadow-2xl" id="lookbook-main">
        <?php require __DIR__ . '/partials/lookbook-main.php'; ?>
      </div>

      <div class="lg:col-span-4 space-y-4">
        <h4 class="font-serif-heading text-xl font-bold text-neutral-200 border-b border-neutral-800 pb-3">SHOP THIS EDITORIAL</h4>
        <div class="space-y-3" id="lookbook-list">
          <?php require __DIR__ . '/partials/lookbook-list.php'; ?>
        </div>
      </div>
    </div>
  </div>
</section>
</div>
