<?php
/**
 * Mirrors src/components/HeroBanner.tsx
 * Countdown timer + auto-looping highlight cards are driven by assets/js/app.js
 * using the JSON payload embedded below (#hero-data).
 */
$card1Items = [
    [
        'badge' => 'BESTSELLER',
        'badgeColor' => 'bg-black text-white border-neutral-400/20',
        'name' => 'Kenya Flag Map Graphic Tee',
        'description' => '100% combed ringspun cotton with authentic Kenyan map outline print.',
        'price' => 'Ksh 2,000',
        'image' => 'https://i.pinimg.com/736x/42/ca/e8/42cae8157184ad897c03ab44f65b646d.jpg',
    ],
    [
        'badge' => 'NEW DROP',
        'badgeColor' => 'bg-neutral-900 text-neutral-200 border-neutral-500/30',
        'name' => 'Kenyan Heritage Shield Emblem Tee',
        'description' => 'Premium luxury cotton t-shirt with handcrafted Kenyan shield crest.',
        'price' => 'Ksh 2,500',
        'image' => 'https://i.pinimg.com/736x/15/d0/cc/15d0cc250871a0c23878f9c62750786c.jpg',
    ],
];
$card2Items = [
    [
        'badge' => 'AUTHENTIC',
        'badgeColor' => 'bg-white text-black border-neutral-300',
        'name' => 'Traditional Maasai Shuka Blanket',
        'description' => 'Handcrafted acrylic tartan plaid fabric wraps in vibrant Kenyan colorways.',
        'price' => 'Ksh 3,000',
        'image' => 'https://i.pinimg.com/736x/64/17/2c/64172c5b9151cb1098312954e00fcd17.jpg',
    ],
    [
        'badge' => 'EXCLUSIVE',
        'badgeColor' => 'bg-black text-white border-neutral-400/20',
        'name' => 'Emerald & Gold Kenya Silk Wrap',
        'description' => 'Hand-dyed luxury Kenyan silk shawl with rich heritage patterns.',
        'price' => 'Ksh 4,500',
        'image' => 'https://i.pinimg.com/736x/43/0f/2e/430f2e3534c2538bf36a5127d9bd87db.jpg',
    ],
];
?>
<div class="w-full bg-[#0a0a0a] text-white pt-2 sm:pt-4 pb-6 px-3 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto space-y-4">
    <div class="relative rounded-2xl sm:rounded-3xl bg-black p-4 sm:p-7 lg:p-9 shadow-xl border border-neutral-800 overflow-hidden">

      <div class="relative z-10 flex flex-row items-center justify-between gap-2 sm:gap-4 mb-4 sm:mb-6">
        <div id="hero-shop-now" class="flex items-center space-x-2 sm:space-x-4 cursor-pointer group">
          <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight uppercase group-hover:text-white transition-colors">Shop now</h1>
          <button type="button" class="w-9 h-9 sm:w-13 sm:h-13 rounded-full bg-white text-black hover:bg-white transition-all duration-300 flex items-center justify-center shadow-md cursor-pointer group-hover:scale-105 shrink-0 border border-neutral-300/40" aria-label="Shop now">
            <svg class="w-4 h-4 sm:w-6 sm:h-6 transform group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </div>

        <div class="bg-black border border-neutral-400/40 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full flex items-center space-x-1.5 sm:space-x-2 text-xs sm:text-sm font-bold text-white shadow-sm shrink-0">
          <span class="text-neutral-100 font-medium text-[10px] sm:text-xs uppercase tracking-wider hidden xs:inline">Ends in:</span>
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white animate-pulse shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span id="hero-countdown" class="font-mono text-white tracking-wider text-xs sm:text-sm font-bold">10:59:40</span>
        </div>
      </div>

      <div class="relative z-10 mb-6 max-w-xl">
        <div class="bg-black border-l-4 border-neutral-400 px-3.5 py-2 rounded-r-lg inline-block">
          <p class="text-xs sm:text-lg font-bold text-neutral-100 tracking-wide">Exclusive deals on Kenyan magical stuffs</p>
        </div>
      </div>

      <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-5">
        <div class="hero-highlight-card bg-white rounded-xl sm:rounded-2xl p-3.5 sm:p-4 shadow-xl border border-neutral-200 hover:border-neutral-400 transition-all cursor-pointer group relative overflow-hidden" data-hero-explore data-card="1">
          <div class="flex items-center justify-between text-[10px] text-neutral-400 uppercase font-bold tracking-wider mb-2 pb-1 border-b border-neutral-100">
            <span class="text-neutral-800 flex items-center gap-1 font-bold">
              <svg class="w-3 h-3 text-neutral-500" viewBox="0 0 24 24" fill="currentColor"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
              COLLECTION HIGHLIGHT 1
            </span>
            <span class="flex items-center gap-1 text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded-full text-[9px]">
              <span data-hero-index="1">1</span> / <?= count($card1Items) ?>
              <svg class="w-3 h-3 text-neutral-700 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </span>
          </div>
          <div class="hero-card-content flex items-center gap-3 sm:gap-4 transition-all duration-500 transform animate-slide-down" data-hero-content="1"></div>
          <div class="flex justify-center gap-1.5 mt-3 pt-1" data-hero-dots="1"></div>
        </div>

        <div class="hero-highlight-card bg-white rounded-xl sm:rounded-2xl p-3.5 sm:p-4 shadow-xl border border-neutral-200 hover:border-neutral-400 transition-all cursor-pointer group relative overflow-hidden" data-hero-explore data-card="2">
          <div class="flex items-center justify-between text-[10px] text-neutral-400 uppercase font-bold tracking-wider mb-2 pb-1 border-b border-neutral-100">
            <span class="text-neutral-800 flex items-center gap-1 font-bold">
              <svg class="w-3 h-3 text-neutral-500" viewBox="0 0 24 24" fill="currentColor"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
              COLLECTION HIGHLIGHT 2
            </span>
            <span class="flex items-center gap-1 text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded-full text-[9px]">
              <span data-hero-index="2">1</span> / <?= count($card2Items) ?>
              <svg class="w-3 h-3 text-neutral-700 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </span>
          </div>
          <div class="hero-card-content flex items-center gap-3 sm:gap-4 transition-all duration-500 transform animate-slide-down" data-hero-content="2"></div>
          <div class="flex justify-center gap-1.5 mt-3 pt-1" data-hero-dots="2"></div>
        </div>
      </div>

    </div>

    <!-- Feature Badges Ribbon -->
    <div class="bg-black rounded-xl border border-neutral-300 text-neutral-100 py-3 px-3 sm:px-4 shadow-md">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center divide-y md:divide-y-0 md:divide-x divide-neutral-500/20">
        <div class="flex items-center justify-center space-x-2 py-1 md:py-0">
          <svg class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
          <div class="text-left">
            <p class="text-[11px] sm:text-xs font-bold text-white uppercase tracking-wider">Fast Dispatch</p>
            <p class="text-[9px] sm:text-[10px] text-neutral-300">Same day delivery in Nairobi</p>
          </div>
        </div>
        <div class="flex items-center justify-center space-x-2 py-1 md:py-0">
          <svg class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg>
          <div class="text-left">
            <p class="text-[11px] sm:text-xs font-bold text-white uppercase tracking-wider">Authentic Goods</p>
            <p class="text-[9px] sm:text-[10px] text-neutral-300">100% genuine craftsmanship</p>
          </div>
        </div>
        <div class="flex items-center justify-center space-x-2 py-1 md:py-0">
          <svg class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
          <div class="text-left">
            <p class="text-[11px] sm:text-xs font-bold text-white uppercase tracking-wider">Easy Returns</p>
            <p class="text-[9px] sm:text-[10px] text-neutral-300">30-day hassle-free policy</p>
          </div>
        </div>
        <div class="flex items-center justify-center space-x-2 py-1 md:py-0">
          <svg class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.79 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
          <div class="text-left">
            <p class="text-[11px] sm:text-xs font-bold text-white uppercase tracking-wider">M-Pesa Express</p>
            <p class="text-[9px] sm:text-[10px] text-neutral-300">Instant STK push payment</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<script id="hero-data" type="application/json">
<?= json_encode(['card1' => $card1Items, 'card2' => $card2Items], JSON_UNESCAPED_SLASHES) ?>
</script>
