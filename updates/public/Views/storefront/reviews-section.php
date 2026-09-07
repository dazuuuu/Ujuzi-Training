<?php
/**
 * Mirrors src/components/ReviewsSection.tsx — fully static, no client re-render needed.
 * Requires $reviews in scope.
 */
$instagramShots = [
    'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=600&q=80',
];
$starSvg = '<svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/></svg>';
?>
<section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-16">
  <div>
    <div class="text-center max-w-xl mx-auto space-y-2 mb-12">
      <span class="text-xs font-bold text-neutral-700 uppercase tracking-widest block">Client Testimonials</span>
      <h2 class="font-serif-heading text-3xl sm:text-4xl font-bold tracking-tight text-[#1a1a1a]">PRAISED BY CONNOISSEURS</h2>
      <div class="flex items-center justify-center space-x-1 text-neutral-500 pt-1">
        <?php for ($i = 0; $i < 5; $i++) echo $starSvg; ?>
        <span class="text-xs font-bold text-neutral-800 ml-2">4.92 / 5.0 Average Rating</span>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($reviews as $rev): ?>
        <div class="bg-white p-6 rounded-xs border border-neutral-200 shadow-xs flex flex-col justify-between space-y-4">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex text-white">
                <?php for ($i = 0; $i < $rev['rating']; $i++) echo str_replace('w-4 h-4', 'w-3.5 h-3.5', $starSvg); ?>
              </div>
              <span class="text-[10px] text-neutral-400 font-mono"><?= e($rev['date']) ?></span>
            </div>
            <h4 class="font-serif-heading font-bold text-base text-neutral-900 leading-snug">"<?= e($rev['title']) ?>"</h4>
            <p class="text-xs text-neutral-600 font-light leading-relaxed"><?= e($rev['comment']) ?></p>
          </div>
          <div class="pt-3 border-t border-neutral-100 flex items-center space-x-3">
            <img src="<?= e($rev['productImage'] ?? '') ?>" alt="<?= e($rev['productName'] ?? $rev['title'] ?? $rev['author'] ?? '') ?>" class="w-10 h-12 object-cover rounded-xs border border-neutral-200" />
            <div class="text-xs">
              <p class="font-bold text-neutral-900"><?= e($rev['author'] ?? '') ?></p>
              <p class="text-[10px] text-neutral-400 flex items-center gap-1">
                <svg class="w-3 h-3 text-neutral-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                Verified Buyer • <?= e($rev['location']) ?>
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="pt-10 border-t border-neutral-200">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8">
      <div>
        <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest block mb-1">As Styled By You</span>
        <h3 class="font-serif-heading text-2xl sm:text-3xl font-bold text-[#1a1a1a]">#PENTAGONCOLLECTIONS ON INSTAGRAM</h3>
      </div>
      <a href="https://instagram.com" target="_blank" rel="noreferrer" class="inline-flex items-center space-x-2 text-xs font-bold tracking-widest uppercase text-neutral-900 hover:text-neutral-800 transition-colors mt-3 md:mt-0">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
        <span>FOLLOW @PENTAGON.COLLECTIONS</span>
      </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <?php foreach ($instagramShots as $img): ?>
        <div class="group relative aspect-square bg-neutral-100 overflow-hidden rounded-xs cursor-pointer">
          <img src="<?= e($img) ?>" alt="" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700 filter brightness-95" />
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
