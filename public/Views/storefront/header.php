<?php
/**
 * Interactivity (scroll shadow, mobile drawer, currency dropdown, active nav state,
 * cart/wishlist counts) is handled client-side in assets/js/app.js.
 * Requires $categories, $occasions in scope.
 */
$categoryLinks = array_map(fn(array $category): array => [
    'id' => $category['id'],
    'label' => $category['name'],
], $categories ?? []);
$navLinks = array_merge([
    ['id' => 'all', 'label' => 'All Products'],
    ['id' => 'new', 'label' => 'New Arrivals'],
], $categoryLinks);
$occasionLinks = array_map(fn(array $occasion): array => [
    'id' => $occasion['id'],
    'label' => $occasion['label'],
], $occasions ?? []);
$browseLinks = array_merge($navLinks, $occasionLinks);
$currencyLabels = [
    'KSH' => 'Ksh (KSH)',
    'USD' => '$ (USD)',
    'EUR' => '€ (EUR)',
    'GBP' => '£ (GBP)',
];
?>
<header class="sticky top-0 z-40 w-full bg-white" id="site-header">
  <div class="store-top-strip">
    <div class="store-nav-inner store-top-strip-inner">
      <span>(+254) 747900900</span>
      <a id="top-track-order" class="store-track-btn" href="<?= url('/track-order') ?>">Track My Order</a>
    </div>
  </div>

  <div id="header-bar" class="store-main-nav transition-all duration-200">
    <div class="store-nav-inner store-main-nav-inner">

      <!-- Mobile Left: Menu Hamburger -->
      <div class="flex items-center lg:hidden">
        <button id="mobile-menu-toggle" class="store-icon-btn" aria-label="Toggle menu">
          <svg id="icon-menu" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
          <svg id="icon-x" class="w-6 h-6 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
      </div>

      <!-- Center Brand Logo -->
      <div class="store-brand-wrap">
        <button data-select-category="all" class="store-brand nav-select">
          <span class="store-brand-mark"><?= storeLogoHtml('w-full h-full object-contain', 'w-4 h-4') ?></span>
          <span class="store-brand-text">PENTAGON</span>
        </button>
      </div>

      <!-- Desktop Links -->
      <nav class="store-primary-links" id="desktop-nav-links">
        <?php foreach (array_slice($browseLinks, 0, 6) as $link): ?>
          <button data-select-category="<?= e($link['id']) ?>" class="nav-select store-primary-link" data-nav-id="<?= e($link['id']) ?>">
            <?= e($link['label']) ?>
            <span class="nav-active-underline hidden"></span>
          </button>
        <?php endforeach; ?>
      </nav>

      <!-- Right Action Utilities -->
      <div class="store-actions">

        <button id="open-search" class="store-search-pill" title="Search collection" aria-label="Search">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <span>Search products</span>
        </button>

        <!-- Currency Selector (Desktop / Tablet) -->
        <div class="relative hidden sm:block">
          <button id="currency-dropdown-toggle" class="store-currency-btn">
            <span id="currency-label-header">KSH</span>
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div id="currency-dropdown-menu" class="hidden store-currency-menu">
            <?php foreach ($currencyLabels as $code => $label): ?>
              <button data-select-currency="<?= e($code) ?>" class="currency-option store-currency-option"><?= e($label) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Wishlist Button -->
        <button id="open-wishlist" class="store-icon-btn relative" title="View Wishlist" aria-label="Wishlist">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
          <span id="wishlist-count-badge" class="hidden store-count-badge">0</span>
        </button>

        <!-- Shopping Cart Button -->
        <button id="open-cart" class="store-icon-btn relative" title="View Cart" aria-label="Cart">
          <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          <span id="cart-count-badge" class="hidden store-count-badge">0</span>
        </button>
      </div>

    </div>

    <div class="store-category-row">
      <div class="store-nav-inner store-category-scroller" id="category-pills">
        <?php foreach ($browseLinks as $link): ?>
          <button data-select-category="<?= e($link['id']) ?>" class="cat-pill store-category-link" data-cat="<?= e($link['id']) ?>">
            <?= e($link['label']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Mobile Drawer Navigation -->
  <div id="mobile-drawer" class="fixed inset-0 z-50 lg:hidden hidden">
    <div id="mobile-drawer-backdrop" class="fixed inset-0 bg-black/70 transition-opacity"></div>
    <div class="relative w-4/5 max-w-xs bg-white text-black h-full shadow-2xl flex flex-col justify-between z-10 p-5 overflow-y-auto border-r border-neutral-200">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-neutral-200">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 bg-black text-white flex items-center justify-center rounded-md">
              <?= storeLogoHtml('w-full h-full object-contain rounded-md', 'w-4 h-4 text-white') ?>
            </div>
            <span class="font-extrabold text-base tracking-widest text-black uppercase">PENTAGON</span>
          </div>
          <button id="mobile-drawer-close" class="p-1.5 text-black hover:text-neutral-600 cursor-pointer rounded-md">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </button>
        </div>

        <div class="mt-4">
          <button id="mobile-open-search" class="w-full flex items-center justify-between bg-neutral-100 border border-neutral-200 text-neutral-500 px-3 py-2 rounded-md text-xs font-medium cursor-pointer">
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              Search goods...
            </span>
            <span class="text-[10px] bg-black text-white px-1.5 py-0.5 rounded">Find</span>
          </button>
        </div>

        <div class="py-5 space-y-1">
          <p class="text-[10px] uppercase tracking-widest text-neutral-500 font-bold mb-2">Browse Categories</p>
          <?php foreach ($browseLinks as $link): ?>
            <button data-select-category="<?= e($link['id']) ?>" class="nav-select-mobile block w-full text-left py-2.5 px-3 rounded-md text-xs tracking-wider uppercase font-semibold transition-all text-neutral-800 hover:bg-neutral-100" data-nav-id="<?= e($link['id']) ?>">
              <?= e($link['label']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pt-4 border-t border-neutral-200 space-y-3 text-xs text-neutral-700">
        <div class="flex items-center justify-between">
          <span class="text-neutral-500 text-xs font-medium">Currency:</span>
          <div class="flex space-x-1" id="mobile-currency-options">
            <?php foreach (array_keys($currencyLabels) as $code): ?>
              <button data-select-currency="<?= e($code) ?>" class="currency-option-mobile px-2 py-1 rounded text-[11px] font-bold cursor-pointer bg-white text-black border border-neutral-300"><?= e($code) ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <button id="mobile-open-size-guide" class="w-full text-left py-1 text-xs text-black hover:underline cursor-pointer">
          Size Guide &amp; Product Specifications
        </button>

        <p class="text-[10px] text-neutral-400 pt-2 border-t border-neutral-200">
          © Pentagon Collections. Nairobi, Kenya.
        </p>
      </div>
    </div>
  </div>
</header>
