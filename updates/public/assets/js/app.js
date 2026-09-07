/**
 * Pentagon Collections — vanilla JS behavior layer.
 * Replaces React's component state/rendering with plain DOM updates while
 * PHP (public/Views/storefront/*.php, rendered by StorefrontController) owns
 * the initial server-rendered markup.
 * Section comments below correspond 1:1 to the original src/components/*.tsx files.
 */
(function () {
  'use strict';

  var DATA = JSON.parse(document.getElementById('pentagon-data').textContent);
  var PLACEHOLDER_IMAGE = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20800%201000%22%3E%3Crect%20width%3D%22800%22%20height%3D%221000%22%20fill%3D%22%23f5f5f5%22/%3E%3Cpath%20d%3D%22M260%20470h280v60H260z%22%20fill%3D%22%23d4d4d4%22/%3E%3Cpath%20d%3D%22M310%20380h180v60H310z%22%20fill%3D%22%23e5e5e5%22/%3E%3Ctext%20x%3D%22400%22%20y%3D%22580%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2232%22%20fill%3D%22%23737373%22%20text-anchor%3D%22middle%22%3ENo%20image%3C/text%3E%3C/svg%3E';
  var PRODUCTS = (DATA.products || []).map(function (product) {
    product.images = Array.isArray(product.images) ? product.images.filter(Boolean) : [];
    product.colors = Array.isArray(product.colors) ? product.colors : [];
    return product;
  });
  var CATEGORIES = DATA.categories || [];
  var OCCASIONS = DATA.occasions || [];
  var LOOKBOOK = DATA.lookbook;
  var REVIEWS = DATA.reviews;
  var BASE_URL = (DATA.baseUrl || '/').replace(/\/$/, '');

  function siteUrl(path) {
    return BASE_URL + '/' + String(path || '').replace(/^\//, '');
  }

  // Product/category images are either an absolute seed URL or an uploaded
  // path relative to the app root (assets/uploads/products/xyz.jpg).
  function imgUrl(path) {
    if (!path) return PLACEHOLDER_IMAGE;
    if (/^data:/i.test(path)) return path;
    if (/^https?:\/\//i.test(path)) return path;
    return siteUrl(path);
  }

  var ICONS = {
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    heart: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
    shoppingBag: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    shoppingCart: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
    trash2: '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
    arrowRight: '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    sparkles: '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>',
    tag: '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
    check: '<path d="M20 6 9 17l-5-5"/>',
    checkCircle: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
    shieldCheck: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.79 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
    lock: '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    creditCard: '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
    smartphone: '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
    star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26"/>',
    rotateCcw: '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
    truck: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
    eye: '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
    filter: '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
    zap: '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
  };

  function icon(name, cls, fill) {
    return '<svg class="' + cls + '" viewBox="0 0 24 24" fill="' + (fill || 'none') + '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + ICONS[name] + '</svg>';
  }

  function esc(str) {
    var div = document.createElement('div');
    div.textContent = String(str == null ? '' : str);
    return div.innerHTML;
  }

  function formatPrice(amountUsd, currency) {
    if (currency === 'KSH') {
      return 'Ksh ' + Math.round(amountUsd * 100).toLocaleString('en-US');
    }
    var rate = currency === 'EUR' ? 0.92 : currency === 'GBP' ? 0.79 : 1.0;
    var symbol = currency === 'EUR' ? '€' : currency === 'GBP' ? '£' : '$';
    return symbol + Math.round(amountUsd * rate).toLocaleString('en-US');
  }

  function findProduct(id) {
    for (var i = 0; i < PRODUCTS.length; i++) if (PRODUCTS[i].id === id) return PRODUCTS[i];
    return null;
  }

  // ---------------------------------------------------------------------
  // Global app state (mirrors App.tsx useState hooks)
  // ---------------------------------------------------------------------
  var state = {
    currentCategory: 'all',
    sortBy: 'featured',
    currency: DATA.currency || 'KSH',
    cart: [], // { productId, selectedColor:{name,hex}, selectedSize, quantity }
    wishlist: [], // array of productId
    appliedDiscountPercent: 0,
    activeLookIndex: 0,
  };

  var openModalStack = [];

  function trapEscape() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && openModalStack.length) {
        var closer = openModalStack[openModalStack.length - 1];
        closer();
      }
    });
  }

  // ---------------------------------------------------------------------
  // Header — mirrors src/components/Header.tsx
  // ---------------------------------------------------------------------
  function initHeader() {
    var headerBar = document.getElementById('header-bar');
    window.addEventListener('scroll', function () {
      var scrolled = window.scrollY > 20;
      headerBar.classList.toggle('is-scrolled', scrolled);
    });

    var mobileToggle = document.getElementById('mobile-menu-toggle');
    var mobileDrawer = document.getElementById('mobile-drawer');
    var mobileBackdrop = document.getElementById('mobile-drawer-backdrop');
    var mobileClose = document.getElementById('mobile-drawer-close');
    var iconMenu = document.getElementById('icon-menu');
    var iconX = document.getElementById('icon-x');

    function setMobileMenu(open) {
      mobileDrawer.classList.toggle('hidden', !open);
      iconMenu.classList.toggle('hidden', open);
      iconX.classList.toggle('hidden', !open);
    }
    mobileToggle.addEventListener('click', function () {
      setMobileMenu(mobileDrawer.classList.contains('hidden'));
    });
    mobileBackdrop.addEventListener('click', function () { setMobileMenu(false); });
    mobileClose.addEventListener('click', function () { setMobileMenu(false); });
    document.getElementById('mobile-open-search').addEventListener('click', function () {
      setMobileMenu(false);
      openSearchModal();
    });
    document.getElementById('mobile-open-size-guide').addEventListener('click', function () {
      setMobileMenu(false);
      openSizeGuide();
    });
    window.__closeMobileMenu = function () { setMobileMenu(false); };

    var currencyToggle = document.getElementById('currency-dropdown-toggle');
    var currencyMenu = document.getElementById('currency-dropdown-menu');
    currencyToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      currencyMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', function () { currencyMenu.classList.add('hidden'); });

    updateHeaderUI();
  }

  function setActiveNav(category) {
    document.querySelectorAll('#desktop-nav-links [data-nav-id]').forEach(function (btn) {
      var active = btn.getAttribute('data-nav-id') === category;
      btn.classList.toggle('is-active', active);
      btn.querySelector('.nav-active-underline').classList.toggle('hidden', !active);
    });
    document.querySelectorAll('.nav-select-mobile[data-nav-id]').forEach(function (btn) {
      var active = btn.getAttribute('data-nav-id') === category;
      btn.classList.toggle('is-active', active);
    });
    document.querySelectorAll('.cat-pill[data-cat]').forEach(function (btn) {
      var active = btn.getAttribute('data-cat') === category;
      btn.classList.toggle('is-active', active);
    });
    document.querySelectorAll('.store-side-category[data-nav-id]').forEach(function (btn) {
      var active = btn.getAttribute('data-nav-id') === category;
      btn.classList.toggle('is-active', active);
    });
  }

  function updateHeaderUI() {
    var cartCount = state.cart.reduce(function (a, i) { return a + i.quantity; }, 0);
    var cartBadge = document.getElementById('cart-count-badge');
    cartBadge.textContent = cartCount;
    cartBadge.classList.toggle('hidden', cartCount === 0);

    var wishBadge = document.getElementById('wishlist-count-badge');
    wishBadge.textContent = state.wishlist.length;
    wishBadge.classList.toggle('hidden', state.wishlist.length === 0);

    document.getElementById('currency-label-header').textContent = state.currency;
    document.querySelectorAll('.currency-option, .currency-option-mobile, .currency-option-footer').forEach(function (btn) {
      var active = btn.getAttribute('data-select-currency') === state.currency;
      btn.classList.toggle('is-active', active);
    });
  }

  function setCurrency(c) {
    state.currency = c;
    updateHeaderUI();
    renderProductsSection();
    refreshOpenModals();
  }

  // ---------------------------------------------------------------------
  // Hero Banner — mirrors src/components/HeroBanner.tsx
  // ---------------------------------------------------------------------
  function initHero() {
    var heroData = JSON.parse(document.getElementById('hero-data').textContent);
    var timeLeft = { hours: 10, minutes: 59, seconds: 40 };
    var countdownEl = document.getElementById('hero-countdown');
    function two(n) { return String(n).padStart(2, '0'); }
    setInterval(function () {
      if (timeLeft.seconds > 0) timeLeft.seconds--;
      else if (timeLeft.minutes > 0) { timeLeft.minutes--; timeLeft.seconds = 59; }
      else if (timeLeft.hours > 0) { timeLeft.hours--; timeLeft.minutes = 59; timeLeft.seconds = 59; }
      else { timeLeft = { hours: 10, minutes: 59, seconds: 40 }; }
      countdownEl.textContent = two(timeLeft.hours) + ':' + two(timeLeft.minutes) + ':' + two(timeLeft.seconds);
    }, 1000);

    setupHeroCard(1, heroData.card1, 3500);
    setupHeroCard(2, heroData.card2, 4000);

    document.querySelectorAll('[data-hero-explore]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        if (e.target.closest('[data-hero-dot]')) return;
        state.currentCategory = 'all';
        setActiveNav('all');
        renderProductsSection();
        document.getElementById('products-section').scrollIntoView({ behavior: 'smooth' });
      });
    });
  }

  function heroCardContentHTML(item) {
    return (
      '<div class="w-24 sm:w-32 h-28 sm:h-36 rounded-lg overflow-hidden bg-neutral-100 shrink-0 border border-neutral-200">' +
      '<img src="' + esc(item.image) + '" alt="' + esc(item.name) + '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />' +
      '</div>' +
      '<div class="flex-1 text-neutral-900 min-w-0">' +
      '<span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider mb-1 border ' + item.badgeColor + '">' + esc(item.badge) + '</span>' +
      '<h3 class="font-serif-heading font-bold text-xs sm:text-base text-neutral-900 leading-tight truncate group-hover:text-neutral-800 transition-colors">' + esc(item.name) + '</h3>' +
      '<p class="text-[10px] sm:text-xs text-neutral-500 mt-1 line-clamp-2">' + esc(item.description) + '</p>' +
      '<div class="mt-2 sm:mt-3 flex items-center justify-between">' +
      '<span class="text-xs sm:text-base font-black text-[#8b1c1c]">' + esc(item.price) + '</span>' +
      '<span class="text-[10px] sm:text-xs font-bold text-neutral-900 underline group-hover:text-neutral-800 flex items-center gap-1">Shop ' + icon('arrowRight', 'w-3 h-3 text-neutral-700') + '</span>' +
      '</div></div>'
    );
  }

  function setupHeroCard(cardNum, items, intervalMs) {
    var index = 0;
    var contentEl = document.querySelector('[data-hero-content="' + cardNum + '"]');
    var dotsEl = document.querySelector('[data-hero-dots="' + cardNum + '"]');
    var indexLabel = document.querySelector('[data-hero-index="' + cardNum + '"]');

    function render() {
      contentEl.innerHTML = heroCardContentHTML(items[index]);
      contentEl.classList.remove('animate-slide-down');
      void contentEl.offsetWidth;
      contentEl.classList.add('animate-slide-down');
      indexLabel.textContent = index + 1;
      dotsEl.innerHTML = items.map(function (_, idx) {
        return '<button data-hero-dot class="h-1.5 rounded-full transition-all cursor-pointer ' + (idx === index ? 'w-5 bg-neutral-800' : 'w-1.5 bg-neutral-300') + '" data-idx="' + idx + '"></button>';
      }).join('');
      dotsEl.querySelectorAll('[data-hero-dot]').forEach(function (dot) {
        dot.addEventListener('click', function (e) {
          e.stopPropagation();
          index = parseInt(dot.getAttribute('data-idx'), 10);
          render();
        });
      });
    }
    render();
    setInterval(function () {
      index = (index + 1) % items.length;
      render();
    }, intervalMs);
  }

  function initOffersHero() {
    var carousel = document.getElementById('offer-carousel');
    if (!carousel) return;
    var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-offer-slide]'));
    if (slides.length < 2) return;
    var dots = Array.prototype.slice.call(carousel.querySelectorAll('[data-offer-dot]'));
    var active = 0;

    function show(idx) {
      active = (idx + slides.length) % slides.length;
      slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === active); });
      dots.forEach(function (dot, i) { dot.classList.toggle('is-active', i === active); });
    }

    var next = function () { show(active + 1); };
    var timer = setInterval(next, 5200);
    function restartTimer() {
      clearInterval(timer);
      timer = setInterval(next, 5200);
    }

    var prevBtn = carousel.querySelector('[data-offer-prev]');
    var nextBtn = carousel.querySelector('[data-offer-next]');
    if (prevBtn) prevBtn.addEventListener('click', function () { show(active - 1); restartTimer(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { show(active + 1); restartTimer(); });
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        show(parseInt(dot.getAttribute('data-offer-dot'), 10));
        restartTimer();
      });
    });
  }

  // ---------------------------------------------------------------------
  // Product Grid / Catalog section — mirrors the product section of App.tsx
  // ---------------------------------------------------------------------
  function computeFilteredProducts() {
    var list = PRODUCTS.slice();
    if (state.currentCategory === 'new') {
      list = list.filter(function (p) { return p.isNew; });
    } else if (state.currentCategory !== 'all') {
      list = list.filter(function (p) {
        if (state.currentCategory.indexOf('occasion:') === 0) {
          return String(p.occasion || '') === state.currentCategory.slice(9);
        }
        var subCategory = String(p.subCategory || '');
        var categories = p.categories || [p.category];
        return categories.indexOf(state.currentCategory) !== -1 ||
          p.category === state.currentCategory ||
          subCategory.toLowerCase().indexOf(state.currentCategory.toLowerCase()) !== -1;
      });
    }
    if (state.sortBy === 'price-low') list.sort(function (a, b) { return a.price - b.price; });
    else if (state.sortBy === 'price-high') list.sort(function (a, b) { return b.price - a.price; });
    else if (state.sortBy === 'rating') list.sort(function (a, b) { return b.rating - a.rating; });
    return list;
  }

  function renderProductCardHTML(product, isWishlisted) {
    var formattedPrice = formatPrice(product.price, state.currency);
    var formattedOriginal = product.originalPrice ? formatPrice(product.originalPrice, state.currency) : null;
    var img0 = esc(imgUrl(product.images[0]));
    var img1 = esc(imgUrl(product.images[1] || product.images[0]));
    var wishClasses = isWishlisted ? ' is-active' : '';

    return (
      '<div class="product-card group" data-product-id="' + esc(product.id) + '">' +
        '<div>' +
          '<div class="product-card-image-wrap" data-quickview-trigger data-product-id="' + esc(product.id) + '">' +
            '<img src="' + img0 + '" data-primary="' + img0 + '" data-secondary="' + img1 + '" alt="' + esc(product.name) + '" class="product-card-img w-full h-full object-cover object-center transition-transform duration-200 ease-out pointer-events-none scale-100" />' +
            '<div class="product-card-badges">' +
              (product.isNew ? '<span>NEW</span>' : '') +
              (product.isBestSeller ? '<span>BEST SELLER</span>' : '') +
            '</div>' +
            '<div class="product-card-quickview">' +
              '<span class="product-card-zoom-label hidden">Quick View</span>' +
              '<button data-quickview-trigger data-product-id="' + esc(product.id) + '" title="Quick View Details">' + icon('eye', 'w-4 h-4') + '</button>' +
            '</div>' +
          '</div>' +
          '<div class="product-card-body">' +
            '<div class="product-card-title-row">' +
              '<h3 data-quickview-trigger data-product-id="' + esc(product.id) + '">' + esc(product.name) + '</h3>' +
              '<button data-wishlist-toggle data-product-id="' + esc(product.id) + '" class="wishlist-btn' + wishClasses + '" aria-label="Wishlist">' +
                icon('heart', '', isWishlisted ? 'currentColor' : 'none') +
              '</button></div>' +
            '<div class="product-card-price-row">' +
              '<span class="product-card-price">' + esc(formattedPrice) + '</span>' +
              (formattedOriginal ? '<span class="product-card-original-price">' + esc(formattedOriginal) + '</span>' : '') +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="product-card-actions">' +
          '<button data-buy-now data-product-id="' + esc(product.id) + '" class="product-buy-btn">' +
            icon('zap', '') + '<span>BUY NOW</span></button>' +
          '<button data-quick-add data-product-id="' + esc(product.id) + '" class="product-cart-btn">' +
            icon('shoppingCart', '') + '<span>ADD TO CART</span></button>' +
        '</div>' +
      '</div>'
    );
  }

  function attachProductCardHoverEffects(container) {
    container.querySelectorAll('.product-card-image-wrap').forEach(function (wrap) {
      var img = wrap.querySelector('.product-card-img');
      var zoomLabel = wrap.querySelector('.product-card-zoom-label');
      wrap.addEventListener('mouseenter', function () {
        img.src = img.getAttribute('data-secondary');
        if (zoomLabel) zoomLabel.classList.remove('hidden');
      });
      wrap.addEventListener('mouseleave', function () {
        img.src = img.getAttribute('data-primary');
        img.style.transformOrigin = 'center center';
        img.classList.remove('scale-175');
        img.classList.add('scale-100');
        if (zoomLabel) zoomLabel.classList.add('hidden');
      });
      wrap.addEventListener('mousemove', function (e) {
        var rect = wrap.getBoundingClientRect();
        var x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
        var y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
        img.style.transformOrigin = x + '% ' + y + '%';
        img.classList.remove('scale-100');
        img.classList.add('scale-175');
      });
    });
  }

  var categoryHeadings = { all: 'Recommended for You', new: 'New Arrivals' };
  CATEGORIES.forEach(function (category) {
    categoryHeadings[category.id] = category.name;
  });
  OCCASIONS.forEach(function (occasion) {
    categoryHeadings[occasion.id] = occasion.label;
  });

  function renderProductsSection() {
    var heading = document.getElementById('products-heading');
    heading.textContent = categoryHeadings[state.currentCategory] || state.currentCategory;

    var grid = document.getElementById('product-grid');
    var list = computeFilteredProducts();
    var count = document.getElementById('products-count');
    if (count) count.textContent = list.length + (list.length === 1 ? ' item found' : ' items found');

    if (list.length === 0) {
      grid.className = '';
      grid.innerHTML =
        '<div class="py-16 text-center text-neutral-500 space-y-3">' +
        icon('filter', 'w-8 h-8 mx-auto text-neutral-400') +
        '<p class="font-serif-heading text-lg font-bold">No products found in this category.</p>' +
        '<button id="empty-view-all" class="bg-[#0a0a0a] text-white border border-black text-xs font-bold px-6 py-2.5 rounded-lg uppercase tracking-widest">View All Products</button>' +
        '</div>';
      document.getElementById('empty-view-all').addEventListener('click', function () {
        selectCategory('all');
      });
      return;
    }

    grid.className = 'store-product-grid';
    grid.innerHTML = list.map(function (p) {
      return renderProductCardHTML(p, state.wishlist.indexOf(p.id) !== -1);
    }).join('');
    attachProductCardHoverEffects(grid);
  }

  function selectCategory(cat) {
    state.currentCategory = cat;
    setActiveNav(cat);
    renderProductsSection();
  }

  function initProductSection() {
    document.getElementById('sort-select').addEventListener('change', function (e) {
      state.sortBy = e.target.value;
      renderProductsSection();
    });
    attachProductCardHoverEffects(document.getElementById('product-grid'));
  }

  // ---------------------------------------------------------------------
  // Cart — mirrors src/components/CartDrawer.tsx
  // ---------------------------------------------------------------------
  var cartUiState = { promoCode: '', discountPercent: 0, promoError: '', promoSuccess: '' };

  function defaultProductColor(product) {
    return (product.colors && product.colors[0]) || { name: 'Default', hex: '#000000', image: null };
  }

  function defaultProductSize(product) {
    return (product.sizes && product.sizes[0]) || 'One Size';
  }

  function addToCart(productId, color, size, qty) {
    var product = findProduct(productId);
    if (!product) return;
    color = color || defaultProductColor(product);
    size = size || defaultProductSize(product);
    qty = qty || 1;
    var idx = -1;
    for (var i = 0; i < state.cart.length; i++) {
      var it = state.cart[i];
      if (it.productId === productId && it.selectedColor.name === color.name && it.selectedSize === size) { idx = i; break; }
    }
    if (idx > -1) state.cart[idx].quantity += qty;
    else state.cart.push({ productId: productId, selectedColor: color, selectedSize: size, quantity: qty });
    updateHeaderUI();
  }

  function updateCartQty(idx, qty) {
    state.cart[idx].quantity = Math.max(1, qty);
    updateHeaderUI();
    renderCartDrawer();
  }

  function removeCartItem(idx) {
    state.cart.splice(idx, 1);
    updateHeaderUI();
    renderCartDrawer();
  }

  function cartSubtotalUsd() {
    return state.cart.reduce(function (sum, item) { return sum + findProduct(item.productId).price * item.quantity; }, 0);
  }

  function openCartDrawer() {
    ensureModal('modal-cart');
    renderCartDrawer();
    openModalStack.push(closeCartDrawer);
  }
  function closeCartDrawer() {
    removeModal('modal-cart');
    popModal(closeCartDrawer);
  }

  function renderCartDrawer() {
    var root = document.getElementById('modal-cart');
    if (!root) return;
    var items = state.cart;
    var rawSubtotal = cartSubtotalUsd();
    var discountAmount = Math.round(rawSubtotal * (cartUiState.discountPercent / 100));
    var finalTotal = rawSubtotal - discountAmount;
    var freeShippingThreshold = 100;
    var progressPercent = Math.min(100, Math.round((rawSubtotal / freeShippingThreshold) * 100));
    var amountNeeded = Math.max(0, freeShippingThreshold - rawSubtotal);

    var itemsHTML = items.length === 0
      ? '<div class="h-full flex flex-col items-center justify-center text-center space-y-4 py-12">' +
        '<div class="w-16 h-16 bg-black rounded-full flex items-center justify-center text-white border border-neutral-400/30">' + icon('shoppingBag', 'w-8 h-8') + '</div>' +
        '<div><h3 class="font-serif-heading text-lg font-bold text-neutral-900">Your cart is empty</h3>' +
        '<p class="text-xs text-neutral-500 mt-1 max-w-xs">Browse the collection and add your favorite pieces.</p></div>' +
        '<button data-close-cart class="bg-[#0a0a0a] text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest hover:bg-black transition-colors border border-neutral-400/30">START SHOPPING</button>' +
        '</div>'
      : items.map(function (item, idx) {
          var product = findProduct(item.productId);
          return '<div class="py-4 flex space-x-4">' +
            '<div class="w-20 h-24 bg-neutral-100 rounded-lg overflow-hidden shrink-0 border border-neutral-200"><img src="' + esc(imgUrl(product.images[0])) + '" alt="' + esc(product.name) + '" class="w-full h-full object-cover" /></div>' +
            '<div class="flex-1 flex flex-col justify-between"><div>' +
            '<div class="flex justify-between items-start"><h4 class="font-medium text-sm font-bold text-[#0a0a0a] line-clamp-1">' + esc(product.name) + '</h4>' +
            '<button data-remove-cart-item="' + idx + '" class="text-neutral-400 hover:text-rose-600 p-1 transition-colors cursor-pointer" title="Remove item">' + icon('trash2', 'w-4 h-4') + '</button></div>' +
            '<div class="text-[11px] text-neutral-500 mt-1 space-y-0.5"><p>Option: <span class="font-semibold text-neutral-800">' + esc(item.selectedColor.name) + '</span></p></div></div>' +
            '<div class="flex items-center justify-between mt-3">' +
            '<div class="flex items-center border border-neutral-300 rounded-lg bg-white text-xs">' +
            '<button data-cart-qty-down="' + idx + '" class="px-2.5 py-1 text-neutral-600 hover:text-black font-bold">-</button>' +
            '<span class="px-2 py-1 font-bold min-w-[24px] text-center">' + item.quantity + '</span>' +
            '<button data-cart-qty-up="' + idx + '" class="px-2.5 py-1 text-neutral-600 hover:text-black font-bold">+</button></div>' +
            '<span class="text-sm font-bold text-[#8b1c1c]">' + esc(formatPrice(product.price * item.quantity, state.currency)) + '</span>' +
            '</div></div></div>';
        }).join('');

    var footerHTML = items.length === 0 ? '' :
      '<div class="p-6 bg-white border-t border-neutral-200 space-y-4">' +
      '<form id="promo-form" class="flex gap-2">' +
      '<div class="relative flex-1">' + icon('tag', 'w-3.5 h-3.5 text-neutral-400 absolute left-3 top-3') +
      '<input type="text" id="promo-input" value="' + esc(cartUiState.promoCode) + '" placeholder="Promo code" class="w-full bg-neutral-50 border border-neutral-300 text-xs rounded-lg pl-8 pr-3 py-2 uppercase font-mono focus:outline-none focus:border-black" /></div>' +
      '<button type="submit" class="bg-[#0a0a0a] text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-black uppercase cursor-pointer border border-neutral-400/30">Apply</button></form>' +
      (cartUiState.promoError ? '<p class="text-[11px] text-rose-600">' + esc(cartUiState.promoError) + '</p>' : '') +
      (cartUiState.promoSuccess ? '<p class="text-[11px] text-neutral-800 font-semibold flex items-center gap-1">' + icon('check', 'w-3.5 h-3.5 text-neutral-600') + esc(cartUiState.promoSuccess) + '</p>' : '') +
      '<div class="space-y-1.5 text-xs text-neutral-600 border-t border-neutral-100 pt-3">' +
      '<div class="flex justify-between"><span>Subtotal</span><span class="font-semibold text-neutral-900">' + esc(formatPrice(rawSubtotal, state.currency)) + '</span></div>' +
      (cartUiState.discountPercent > 0 ? '<div class="flex justify-between text-neutral-800 font-medium"><span>Discount (' + cartUiState.discountPercent + '%)</span><span>-' + esc(formatPrice(discountAmount, state.currency)) + '</span></div>' : '') +
      '<div class="flex justify-between"><span>Estimated delivery</span><span class="font-semibold text-neutral-900">' + (amountNeeded === 0 ? 'Free' : esc(formatPrice(15, state.currency))) + '</span></div>' +
      '<div class="flex justify-between text-base font-bold text-[#0a0a0a] pt-2 border-t border-neutral-200"><span>Total Amount</span><span class="text-[#8b1c1c] font-bold">' + esc(formatPrice(finalTotal, state.currency)) + '</span></div>' +
      '</div>' +
      '<button id="proceed-checkout" class="w-full bg-[#0a0a0a] hover:bg-black text-white py-3.5 text-xs font-bold tracking-widest uppercase rounded-lg transition-colors flex items-center justify-center space-x-2 cursor-pointer shadow-md border border-neutral-400/40">' +
      '<span>PROCEED TO CHECKOUT</span>' + icon('arrowRight', 'w-4 h-4 text-white') + '</button>' +
      '</div>';

    root.innerHTML =
      '<div class="fixed inset-0 z-50 overflow-hidden">' +
      '<div class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity" data-close-cart></div>' +
      '<div class="fixed inset-y-0 right-0 max-w-full flex pl-10"><div class="w-screen max-w-md bg-white shadow-2xl flex flex-col justify-between border-l border-neutral-300">' +
      '<div class="p-6 border-b border-neutral-200 bg-white text-black" style="background:#fff;color:#0a0a0a;">' +
      '<div class="flex items-center justify-between"><div class="flex items-center space-x-2" style="color:#0a0a0a;">' + icon('shoppingBag', 'w-5 h-5 text-black') +
      '<h2 class="font-serif-heading text-lg font-bold tracking-wider text-black uppercase" style="color:#0a0a0a;background:transparent;">Shopping Cart</h2>' +
      '<span class="text-xs bg-black text-white font-bold px-2 py-0.5 rounded-full" style="background:#0a0a0a;color:#fff;">' + items.reduce(function (a, i) { return a + i.quantity; }, 0) + '</span></div>' +
      '<button data-close-cart class="p-2 text-neutral-500 hover:text-black transition-colors cursor-pointer" style="color:#737373;" aria-label="Close cart">' + icon('x', 'w-5 h-5') + '</button></div>' +
      '<div class="mt-4 pt-4 border-t border-neutral-200"><div class="flex justify-between text-xs font-semibold uppercase tracking-wider mb-1.5">' +
      (amountNeeded === 0
        ? '<span class="text-black flex items-center gap-1 font-bold" style="color:#0a0a0a;">' + icon('check', 'w-3.5 h-3.5') + 'Free delivery unlocked</span>'
        : '<span class="text-neutral-700" style="color:#404040;">Add ' + esc(formatPrice(amountNeeded, state.currency)) + ' more for free delivery</span>') +
      '<span class="text-black font-mono" style="color:#0a0a0a;">' + progressPercent + '%</span></div>' +
      '<div class="w-full h-2 bg-neutral-200 rounded-full overflow-hidden border border-neutral-300"><div class="h-full bg-black transition-all duration-500" style="width:' + progressPercent + '%;background:#0a0a0a;"></div></div>' +
      '</div></div>' +
      '<div class="flex-1 overflow-y-auto p-6 divide-y divide-neutral-200/80">' + itemsHTML + '</div>' +
      footerHTML +
      '</div></div></div>';

    root.querySelectorAll('[data-close-cart]').forEach(function (el) { el.addEventListener('click', closeCartDrawer); });
    root.querySelectorAll('[data-cart-qty-down]').forEach(function (el) {
      el.addEventListener('click', function () { var i = +el.getAttribute('data-cart-qty-down'); updateCartQty(i, state.cart[i].quantity - 1); });
    });
    root.querySelectorAll('[data-cart-qty-up]').forEach(function (el) {
      el.addEventListener('click', function () { var i = +el.getAttribute('data-cart-qty-up'); updateCartQty(i, state.cart[i].quantity + 1); });
    });
    root.querySelectorAll('[data-remove-cart-item]').forEach(function (el) {
      el.addEventListener('click', function () { removeCartItem(+el.getAttribute('data-remove-cart-item')); });
    });
    var promoForm = document.getElementById('promo-form');
    if (promoForm) {
      promoForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var code = document.getElementById('promo-input').value.trim().toUpperCase();
        cartUiState.promoCode = code;
        cartUiState.promoError = '';
        cartUiState.promoSuccess = '';
        if (code === 'PENTAGON10' || code === 'HIII10' || code === 'WELCOME10') {
          cartUiState.discountPercent = 10;
          cartUiState.promoSuccess = '10% Pentagon Member discount applied!';
        } else if (code === 'PENTAGON20') {
          cartUiState.discountPercent = 20;
          cartUiState.promoSuccess = '20% VIP Atelier discount applied!';
        } else {
          cartUiState.promoError = 'Invalid promo code. Try PENTAGON10';
        }
        renderCartDrawer();
      });
    }
    var proceedBtn = document.getElementById('proceed-checkout');
    if (proceedBtn) {
      proceedBtn.addEventListener('click', function () {
        state.appliedDiscountPercent = cartUiState.discountPercent;
        closeCartDrawer();
        openCheckoutModal();
      });
    }
  }

  // ---------------------------------------------------------------------
  // Wishlist — mirrors src/components/WishlistDrawer.tsx
  // ---------------------------------------------------------------------
  function toggleWishlist(productId) {
    var idx = state.wishlist.indexOf(productId);
    if (idx > -1) state.wishlist.splice(idx, 1);
    else state.wishlist.push(productId);
    updateHeaderUI();
    renderProductsSection();
    refreshOpenModals();
  }

  function openWishlistDrawer() {
    ensureModal('modal-wishlist');
    renderWishlistDrawer();
    openModalStack.push(closeWishlistDrawer);
  }
  function closeWishlistDrawer() {
    removeModal('modal-wishlist');
    popModal(closeWishlistDrawer);
  }

  function renderWishlistDrawer() {
    var root = document.getElementById('modal-wishlist');
    if (!root) return;
    var itemsHTML = state.wishlist.length === 0
      ? '<div class="h-full flex flex-col items-center justify-center text-center space-y-4 py-12">' +
        '<div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center border border-neutral-400/30">' + icon('heart', 'w-8 h-8') + '</div>' +
        '<div><h3 class="font-serif-heading text-lg font-bold text-neutral-900">No items saved yet</h3>' +
        '<p class="text-xs text-neutral-500 mt-1 max-w-xs">Tap the heart icon on any product card to save items to your personal wishlist archive.</p></div>' +
        '<button data-close-wishlist class="bg-[#0a0a0a] text-white text-xs font-bold px-6 py-3 rounded-lg uppercase tracking-widest hover:bg-black border border-neutral-400/30 cursor-pointer">EXPLORE ARCHIVE</button>' +
        '</div>'
      : state.wishlist.map(function (pid) {
          var product = findProduct(pid);
          return '<div class="py-4 flex space-x-4">' +
            '<div class="w-20 h-24 bg-neutral-100 rounded-lg overflow-hidden shrink-0 border border-neutral-200 cursor-pointer" data-quickview-trigger data-product-id="' + esc(pid) + '" data-close-wishlist-after><img src="' + esc(imgUrl(product.images[0])) + '" alt="' + esc(product.name) + '" class="w-full h-full object-cover" /></div>' +
            '<div class="flex-1 flex flex-col justify-between"><div>' +
            '<div class="flex justify-between items-start"><h4 class="font-serif-heading text-sm font-bold text-[#0a0a0a] line-clamp-1 hover:underline cursor-pointer" data-quickview-trigger data-product-id="' + esc(pid) + '" data-close-wishlist-after>' + esc(product.name) + '</h4>' +
            '<button data-remove-wishlist="' + esc(pid) + '" class="text-neutral-400 hover:text-rose-600 p-1 cursor-pointer" title="Remove">' + icon('trash2', 'w-4 h-4') + '</button></div>' +
            '<p class="text-xs text-neutral-500 mt-0.5">' + esc(product.subCategory) + '</p>' +
            '<p class="text-xs font-bold text-[#8b1c1c] mt-1">' + esc(formatPrice(product.price, state.currency)) + '</p></div>' +
            '<button data-quickview-trigger data-product-id="' + esc(pid) + '" data-close-wishlist-after class="mt-3 bg-[#0a0a0a] hover:bg-black text-white text-[11px] font-bold py-2 px-3 rounded-lg uppercase tracking-wider flex items-center justify-center space-x-1.5 cursor-pointer border border-neutral-400/30">' +
            icon('shoppingBag', 'w-3.5 h-3.5 text-white') + '<span>View &amp; Add</span></button>' +
            '</div></div>';
        }).join('');

    root.innerHTML =
      '<div class="fixed inset-0 z-50 overflow-hidden">' +
      '<div class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity" data-close-wishlist></div>' +
      '<div class="fixed inset-y-0 right-0 max-w-full flex pl-10"><div class="w-screen max-w-md bg-white shadow-2xl flex flex-col justify-between border-l border-neutral-300">' +
      '<div class="p-6 border-b border-neutral-200 bg-black text-white flex items-center justify-between">' +
      '<div class="flex items-center space-x-2">' + icon('heart', 'w-5 h-5 text-rose-500 fill-rose-500', 'currentColor') +
      '<h2 class="font-serif-heading text-lg font-bold tracking-wider text-white uppercase">SAVED WISHLIST</h2>' +
      '<span class="text-xs bg-white text-black font-bold px-2 py-0.5 rounded-full">' + state.wishlist.length + '</span></div>' +
      '<button data-close-wishlist class="p-2 text-neutral-200 hover:text-white cursor-pointer">' + icon('x', 'w-5 h-5') + '</button></div>' +
      '<div class="flex-1 overflow-y-auto p-6 divide-y divide-neutral-200/80">' + itemsHTML + '</div>' +
      '</div></div></div>';

    root.querySelectorAll('[data-close-wishlist]').forEach(function (el) { el.addEventListener('click', closeWishlistDrawer); });
    root.querySelectorAll('[data-close-wishlist-after]').forEach(function (el) {
      el.addEventListener('click', function () { closeWishlistDrawer(); });
    });
    root.querySelectorAll('[data-remove-wishlist]').forEach(function (el) {
      el.addEventListener('click', function () { toggleWishlist(el.getAttribute('data-remove-wishlist')); });
    });
  }

  // ---------------------------------------------------------------------
  // Quick View — mirrors src/components/QuickViewModal.tsx
  // ---------------------------------------------------------------------
  var qvState = null;

  function openQuickView(productId) {
    var product = findProduct(productId);
    if (!product) return;
    qvState = { productId: productId, selectedImg: 0, selectedColor: defaultProductColor(product), selectedSize: defaultProductSize(product), quantity: 1, addedToast: false };
    ensureModal('modal-quickview');
    renderQuickView();
    openModalStack.push(closeQuickView);
  }
  function closeQuickView() {
    qvState = null;
    removeModal('modal-quickview');
    popModal(closeQuickView);
  }

  function renderQuickView() {
    var root = document.getElementById('modal-quickview');
    if (!root || !qvState) return;
    var product = findProduct(qvState.productId);
    var formattedPrice = formatPrice(product.price, state.currency);
    var formattedOriginal = product.originalPrice ? formatPrice(product.originalPrice, state.currency) : null;
    var isWishlisted = state.wishlist.indexOf(product.id) !== -1;

    var thumbsHTML = product.images.length > 1
      ? '<div class="flex gap-2 overflow-x-auto pb-1">' + product.images.map(function (img, idx) {
          return '<button data-qv-thumb="' + idx + '" class="relative w-16 aspect-square rounded-lg overflow-hidden border-2 transition-all cursor-pointer ' + (qvState.selectedImg === idx ? 'border-neutral-500 scale-105' : 'border-transparent opacity-70 hover:opacity-100') + '"><img src="' + esc(imgUrl(img)) + '" alt="" class="w-full h-full object-cover" /></button>';
        }).join('') + '</div>'
      : '';

    var colorsHTML = product.colors.map(function (c) {
      var active = qvState.selectedColor.name === c.name;
      return '<button data-qv-color="' + esc(c.name) + '" class="flex items-center space-x-2 px-3 py-1.5 rounded-md border text-xs cursor-pointer ' + (active ? 'border-black bg-[#0a0a0a] text-white font-bold' : 'border-neutral-300 bg-white text-neutral-800 hover:border-neutral-400') + '">' +
        '<span class="w-3 h-3 rounded-full border border-current" style="background-color:' + esc(c.hex || '#000000') + '"></span><span>' + esc(c.name) + '</span></button>';
    }).join('');
    var colorOptionsHTML = product.colors.length
      ? '<div class="mt-4"><div class="flex justify-between items-center text-xs font-bold uppercase tracking-wider mb-2"><span>Option: <span class="text-neutral-600 font-normal">' + esc(qvState.selectedColor.name) + '</span></span></div>' +
        '<div class="flex flex-wrap gap-2">' + colorsHTML + '</div></div>'
      : '';

    root.innerHTML =
      '<div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">' +
      '<div class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity" data-close-qv></div>' +
      '<div class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl z-10 overflow-hidden border border-neutral-300 my-6">' +
      '<button data-close-qv class="absolute top-4 right-4 z-20 p-2 bg-[#0a0a0a]/80 hover:bg-[#0a0a0a] text-white rounded-full transition-colors shadow-md cursor-pointer border border-neutral-400/30" aria-label="Close modal">' + icon('x', 'w-5 h-5') + '</button>' +
      '<div class="grid grid-cols-1 md:grid-cols-2">' +
      '<div class="p-6 bg-neutral-50 flex flex-col justify-between">' +
      '<div class="relative aspect-[4/3] sm:aspect-[3/4] w-full rounded-lg overflow-hidden mb-4 bg-white shadow-xs border border-neutral-200">' +
      '<img src="' + esc(imgUrl(product.images[qvState.selectedImg] || product.images[0])) + '" alt="' + esc(product.name) + '" class="w-full h-full object-cover object-center" />' +
      '<button data-qv-wishlist class="absolute top-3 right-3 p-2.5 rounded-full backdrop-blur-md transition-colors cursor-pointer shadow-md ' + (isWishlisted ? 'bg-rose-600 text-white' : 'bg-white/90 text-neutral-800 hover:bg-white') + '">' +
      icon('heart', 'w-5 h-5 ' + (isWishlisted ? 'fill-rose-600 text-rose-600' : ''), isWishlisted ? 'currentColor' : 'none') + '</button></div>' +
      thumbsHTML +
      '</div>' +
      '<div class="p-6 sm:p-8 flex flex-col justify-between space-y-5">' +
      '<div>' +
      '<div class="flex items-center justify-between text-xs text-neutral-500 uppercase tracking-widest font-semibold mb-1">' +
      '<span>' + esc(product.subCategory) + '</span>' +
      '<span class="text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded-md font-bold text-[10px]">' + (product.inStock ? 'IN STOCK • EXPRESS DISPATCH' : 'PRE-ORDER') + '</span>' +
      '</div>' +
      '<h2 class="font-serif-heading text-xl sm:text-2xl font-bold text-[#0a0a0a]">' + esc(product.name) + '</h2>' +
      '<div class="flex items-center space-x-3 mt-2"><div class="flex items-center text-neutral-500">' + icon('star', 'w-4 h-4 fill-neutral-700 text-white', 'currentColor') +
      '<span class="ml-1 text-xs font-bold text-neutral-800">' + product.rating + '</span><span class="ml-1 text-xs text-neutral-400">(' + product.reviewCount + ' reviews)</span></div>' +
      '<span class="text-neutral-300">•</span><span class="text-xs text-neutral-500">' + esc(product.subtitle) + '</span></div>' +
      '<div class="flex items-baseline space-x-3 mt-3"><span class="text-2xl font-bold text-[#8b1c1c]">' + esc(formattedPrice) + '</span>' +
      (formattedOriginal ? '<span class="text-sm text-neutral-400 line-through font-normal">' + esc(formattedOriginal) + '</span>' : '') + '</div>' +
      '<p class="text-xs text-neutral-600 font-light leading-relaxed mt-3 border-t border-b border-neutral-200/80 py-3">' + esc(product.description) + '</p>' +
      colorOptionsHTML +
      '<div class="mt-4 space-y-1 text-xs text-neutral-600 font-light bg-neutral-100 p-3 rounded-lg">' +
      '<p><strong class="font-semibold text-neutral-800">Materials:</strong> ' + esc(product.fabric) + '</p>' +
      '<p><strong class="font-semibold text-neutral-800">Specification:</strong> ' + esc(product.fit) + '</p></div>' +
      '</div>' +
      '<div class="space-y-3 pt-3 border-t border-neutral-200">' +
      '<div class="flex items-center space-x-3">' +
      '<div class="flex items-center border border-neutral-300 rounded-lg bg-white">' +
      '<button data-qv-qty-down class="px-3 py-2 text-sm text-neutral-600 hover:text-black font-bold cursor-pointer">-</button>' +
      '<span class="px-3 py-2 text-xs font-bold min-w-[32px] text-center">' + qvState.quantity + '</span>' +
      '<button data-qv-qty-up class="px-3 py-2 text-sm text-neutral-600 hover:text-black font-bold cursor-pointer">+</button></div>' +
      '<button data-qv-add class="flex-1 bg-[#0a0a0a] hover:bg-black text-white py-3 px-6 text-xs font-bold tracking-widest uppercase rounded-lg transition-colors flex items-center justify-center space-x-2 cursor-pointer shadow-md border border-neutral-400/30">' +
      icon('shoppingBag', 'w-4 h-4 text-white') + '<span>ADD TO CART</span></button>' +
      '</div>' +
      (qvState.addedToast ? '<div class="bg-black text-white text-xs font-bold py-2 px-3 rounded-lg flex items-center justify-center space-x-2 border border-neutral-400/30">' + icon('check', 'w-4 h-4 text-white') + '<span>Item added to your Cart!</span></div>' : '') +
      '<div class="grid grid-cols-3 gap-2 text-[10px] text-neutral-500 text-center pt-1">' +
      '<div class="flex items-center justify-center space-x-1">' + icon('truck', 'w-3.5 h-3.5 text-neutral-700') + '<span>Express Delivery</span></div>' +
      '<div class="flex items-center justify-center space-x-1">' + icon('rotateCcw', 'w-3.5 h-3.5 text-neutral-700') + '<span>30-Day Returns</span></div>' +
      '<div class="flex items-center justify-center space-x-1">' + icon('shieldCheck', 'w-3.5 h-3.5 text-neutral-700') + '<span>100% Authentic</span></div>' +
      '</div></div></div></div></div></div>';

    root.querySelectorAll('[data-close-qv]').forEach(function (el) { el.addEventListener('click', closeQuickView); });
    root.querySelectorAll('[data-qv-thumb]').forEach(function (el) {
      el.addEventListener('click', function () { qvState.selectedImg = +el.getAttribute('data-qv-thumb'); renderQuickView(); });
    });
    root.querySelectorAll('[data-qv-color]').forEach(function (el) {
      el.addEventListener('click', function () {
        var name = el.getAttribute('data-qv-color');
        qvState.selectedColor = product.colors.filter(function (c) { return c.name === name; })[0];
        renderQuickView();
      });
    });
    var wishBtn = root.querySelector('[data-qv-wishlist]');
    if (wishBtn) wishBtn.addEventListener('click', function () { toggleWishlist(product.id); renderQuickView(); });
    var qtyDown = root.querySelector('[data-qv-qty-down]');
    if (qtyDown) qtyDown.addEventListener('click', function () { qvState.quantity = Math.max(1, qvState.quantity - 1); renderQuickView(); });
    var qtyUp = root.querySelector('[data-qv-qty-up]');
    if (qtyUp) qtyUp.addEventListener('click', function () { qvState.quantity++; renderQuickView(); });
    var addBtn = root.querySelector('[data-qv-add]');
    if (addBtn) addBtn.addEventListener('click', function () {
      addToCart(product.id, qvState.selectedColor, qvState.selectedSize, qvState.quantity);
      qvState.addedToast = true;
      renderQuickView();
      setTimeout(function () { if (qvState) { qvState.addedToast = false; renderQuickView(); } }, 2500);
    });
  }

  // ---------------------------------------------------------------------
  // Search — mirrors src/components/SearchModal.tsx
  // ---------------------------------------------------------------------
  var searchQuery = '';
  var popularKeywords = ['Dining Set', 'Umbrella', 'Trench', 'Silk', 'Leather', 'Cashmere'];

  function openSearchModal() {
    searchQuery = '';
    ensureModal('modal-search');
    renderSearchModal();
    openModalStack.push(closeSearchModal);
    setTimeout(function () {
      var input = document.getElementById('search-input');
      if (input) input.focus();
    }, 0);
  }
  function closeSearchModal() {
    removeModal('modal-search');
    popModal(closeSearchModal);
  }

  function renderSearchModal() {
    var root = document.getElementById('modal-search');
    if (!root) return;
    var q = searchQuery.trim().toLowerCase();
    var results = q ? PRODUCTS.filter(function (p) {
      return String(p.name || '').toLowerCase().indexOf(q) !== -1 ||
        String(p.subCategory || '').toLowerCase().indexOf(q) !== -1 ||
        String(p.occasionLabel || '').toLowerCase().indexOf(q) !== -1 ||
        String(p.description || '').toLowerCase().indexOf(q) !== -1 ||
        String(p.fabric || '').toLowerCase().indexOf(q) !== -1;
    }) : [];

    var bodyHTML = !searchQuery
      ? '<div class="space-y-3"><p class="text-xs font-bold text-neutral-900 uppercase tracking-widest flex items-center gap-1">' + icon('sparkles', 'w-3.5 h-3.5 text-neutral-500') + 'Popular Search Terms</p>' +
        '<div class="flex flex-wrap gap-2">' + popularKeywords.map(function (kw) {
          return '<button data-search-kw="' + esc(kw) + '" class="bg-white hover:bg-[#0a0a0a] hover:text-white text-neutral-800 text-xs font-semibold px-3.5 py-1.5 rounded-full border border-neutral-300 transition-colors cursor-pointer">' + esc(kw) + '</button>';
        }).join('') + '</div></div>'
      : '<div class="max-h-[60vh] overflow-y-auto space-y-3 pr-1">' +
        '<p class="text-xs font-semibold text-neutral-500 uppercase tracking-widest">Found ' + results.length + ' Results for "' + esc(searchQuery) + '"</p>' +
        (results.length === 0
          ? '<div class="text-center py-10 text-neutral-500 text-xs font-light">No matching goods found. Try searching for "Dining", "Umbrella", "Trench", or "Leather".</div>'
          : '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' + results.map(function (p) {
              return '<div data-search-result="' + esc(p.id) + '" class="flex items-center space-x-3 p-3 bg-white border border-neutral-200 rounded-lg hover:border-black transition-colors cursor-pointer group shadow-xs">' +
                '<img src="' + esc(imgUrl(p.images[0])) + '" alt="' + esc(p.name) + '" class="w-16 h-20 object-cover rounded-md" />' +
                '<div class="flex-1 min-w-0"><p class="text-[10px] text-neutral-800 font-bold uppercase tracking-wider">' + esc(p.subCategory) + '</p>' +
                '<h4 class="font-serif-heading text-sm font-bold text-neutral-900 truncate group-hover:text-neutral-900">' + esc(p.name) + '</h4>' +
                '<p class="text-xs font-bold text-[#8b1c1c] mt-1">' + esc(formatPrice(p.price, state.currency)) + '</p></div>' +
                icon('arrowRight', 'w-4 h-4 text-neutral-400 group-hover:text-neutral-800 transition-colors') + '</div>';
            }).join('') + '</div>') +
        '</div>';

    root.innerHTML =
      '<div class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4">' +
      '<div class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity" data-close-search></div>' +
      '<div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl z-10 overflow-hidden border border-neutral-300 p-6 space-y-6">' +
      '<div class="flex items-center justify-between border-b border-neutral-300 pb-4">' +
      '<div class="flex items-center space-x-3 flex-1 pr-4">' + icon('search', 'w-5 h-5 text-neutral-800') +
      '<input type="text" id="search-input" value="' + esc(searchQuery) + '" placeholder="Search Pentagon Collections (e.g., Dining Table, Umbrella, Trench)..." class="w-full text-base sm:text-lg font-serif-heading font-semibold bg-transparent border-none focus:outline-none text-neutral-900 placeholder:text-neutral-400" /></div>' +
      '<button data-close-search class="p-2 text-neutral-500 hover:text-black cursor-pointer">' + icon('x', 'w-5 h-5') + '</button></div>' +
      bodyHTML +
      '</div></div>';

    root.querySelectorAll('[data-close-search]').forEach(function (el) { el.addEventListener('click', closeSearchModal); });
    var input = document.getElementById('search-input');
    if (input) {
      input.addEventListener('input', function (e) { searchQuery = e.target.value; renderSearchModal(); setTimeout(function () { var i2 = document.getElementById('search-input'); if (i2) { i2.focus(); i2.setSelectionRange(i2.value.length, i2.value.length); } }, 0); });
    }
    root.querySelectorAll('[data-search-kw]').forEach(function (el) {
      el.addEventListener('click', function () { searchQuery = el.getAttribute('data-search-kw'); renderSearchModal(); });
    });
    root.querySelectorAll('[data-search-result]').forEach(function (el) {
      el.addEventListener('click', function () {
        var pid = el.getAttribute('data-search-result');
        closeSearchModal();
        openQuickView(pid);
      });
    });
  }

  // ---------------------------------------------------------------------
  // Checkout — mirrors src/components/CheckoutModal.tsx
  // ---------------------------------------------------------------------
  var checkoutState = null;

  function defaultCheckoutForm() {
    return {
      firstName: 'Daniel', lastName: 'Kiptoo', email: 'daniel.kiptoo@example.com', phone: '+254 712 345 678',
      address: 'Pentagon Towers, Kilimani', city: 'Nairobi', postalCode: '00100', country: 'Kenya',
      paymentMethod: 'mpesa', mpesaPhone: '254712345678',
      cardNumber: '•••• •••• •••• 4242', expDate: '12/28', cvv: '888',
    };
  }

  function openCheckoutModal() {
    checkoutState = { step: 'details', form: defaultCheckoutForm(), order: null };
    ensureModal('modal-checkout');
    renderCheckoutModal();
    openModalStack.push(closeCheckoutModal);
  }
  function closeCheckoutModal() {
    checkoutState = null;
    removeModal('modal-checkout');
    popModal(closeCheckoutModal);
  }

  function checkoutTotals() {
    var rawSubtotal = cartSubtotalUsd();
    var discountAmount = Math.round(rawSubtotal * (state.appliedDiscountPercent / 100));
    var shipping = rawSubtotal >= 100 ? 0 : 15;
    var total = rawSubtotal - discountAmount + shipping;
    return { rawSubtotal: rawSubtotal, discountAmount: discountAmount, shipping: shipping, total: total };
  }

  function renderCheckoutModal() {
    var root = document.getElementById('modal-checkout');
    if (!root || !checkoutState) return;
    var f = checkoutState.form;
    var totals = checkoutTotals();
    var items = state.cart;

    var bodyHTML;
    if (checkoutState.step === 'processing') {
      bodyHTML = '<div class="py-20 flex items-center justify-center">' + logoLoaderHTML('Authorizing Express Order & Generating Receipt...') + '</div>';
    } else if (checkoutState.step === 'details') {
      var itemsSummary = items.map(function (item) {
        var p = findProduct(item.productId);
        return '<div class="flex gap-3 text-xs bg-white p-2.5 rounded-lg border border-neutral-200"><img src="' + esc(imgUrl(p.images[0])) + '" alt="" class="w-12 h-14 object-cover rounded-md" />' +
          '<div class="flex-1"><p class="font-bold text-neutral-900 line-clamp-1">' + esc(p.name) + '</p>' +
          '<p class="text-[10px] text-neutral-500 mt-0.5">' + esc(item.selectedColor.name) + ' • Qty ' + item.quantity + '</p>' +
          '<p class="font-bold text-[#8b1c1c] mt-1">' + esc(formatPrice(p.price * item.quantity, state.currency)) + '</p></div></div>';
      }).join('');

      var paymentFieldsHTML = f.paymentMethod === 'mpesa'
        ? '<div class="p-4 bg-neutral-50 border border-neutral-300 rounded-lg space-y-2">' +
          '<label class="text-[11px] font-bold text-neutral-900 uppercase block">M-Pesa Phone Number (Prompt will be sent to this phone)</label>' +
          '<input type="text" required id="f-mpesaPhone" value="' + esc(f.mpesaPhone) + '" placeholder="254712345678" class="w-full bg-white border border-neutral-400 rounded-lg p-2 text-xs font-mono font-bold focus:outline-none focus:border-black" />' +
          '<p class="text-[11px] text-neutral-800">You will receive an M-Pesa STK push prompt on your phone upon clicking Place Order.</p></div>'
        : '<div class="space-y-3 pt-1"><div><label class="text-[11px] font-bold text-neutral-600 uppercase">Card Number</label>' +
          '<input type="text" required id="f-cardNumber" value="' + esc(f.cardNumber) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs font-mono focus:outline-none focus:border-black" /></div>' +
          '<div class="grid grid-cols-2 gap-3"><div><label class="text-[11px] font-bold text-neutral-600 uppercase">Exp Date</label>' +
          '<input type="text" required id="f-expDate" value="' + esc(f.expDate) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs font-mono focus:outline-none focus:border-black" /></div>' +
          '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">CVV</label>' +
          '<input type="text" required id="f-cvv" value="' + esc(f.cvv) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs font-mono focus:outline-none focus:border-black" /></div></div></div>';

      bodyHTML =
        '<form id="checkout-form" class="grid grid-cols-1 lg:grid-cols-12">' +
        '<div class="lg:col-span-7 p-5 sm:p-8 space-y-5">' +
        (checkoutState.error ? '<div class="bg-rose-50 border border-rose-300 text-rose-800 text-xs font-semibold rounded-lg p-3">' + esc(checkoutState.error) + '</div>' : '') +
        '<div><span class="text-[11px] font-bold tracking-widest text-neutral-500 uppercase block mb-2">Select Payment Method</span>' +
        '<div class="grid grid-cols-2 gap-3">' +
        '<button type="button" data-payment-method="mpesa" class="py-3 px-3 rounded-lg text-xs font-bold flex items-center justify-center space-x-2 border cursor-pointer ' + (f.paymentMethod === 'mpesa' ? 'bg-black text-white border-neutral-400 ring-2 ring-neutral-400/50' : 'bg-white text-neutral-900 border-neutral-300') + '">' + icon('smartphone', 'w-4 h-4 text-neutral-500') + '<span>M-Pesa Express</span></button>' +
        '<button type="button" data-payment-method="card" class="py-3 px-3 rounded-lg text-xs font-bold flex items-center justify-center space-x-2 border cursor-pointer ' + (f.paymentMethod === 'card' ? 'bg-[#0a0a0a] text-white border-neutral-400 ring-2 ring-neutral-400/50' : 'bg-white text-neutral-900 border-neutral-300') + '">' + icon('creditCard', 'w-4 h-4 text-neutral-500') + '<span>Credit / Debit Card</span></button>' +
        '</div></div>' +
        '<div class="space-y-3"><h3 class="font-serif-heading text-base font-bold text-neutral-900 border-b pb-2 border-neutral-200">Customer &amp; Delivery Details</h3>' +
        '<div class="grid grid-cols-2 gap-3">' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">First Name</label><input type="text" required id="f-firstName" value="' + esc(f.firstName) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Last Name</label><input type="text" required id="f-lastName" value="' + esc(f.lastName) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '</div>' +
        '<div class="grid grid-cols-2 gap-3">' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Email Address</label><input type="email" required id="f-email" value="' + esc(f.email) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Phone Number</label><input type="tel" required id="f-phone" value="' + esc(f.phone) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '</div>' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Street Address / Estate</label><input type="text" required id="f-address" value="' + esc(f.address) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '<div class="grid grid-cols-3 gap-3">' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">City</label><input type="text" required id="f-city" value="' + esc(f.city) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Postcode</label><input type="text" required id="f-postalCode" value="' + esc(f.postalCode) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '<div><label class="text-[11px] font-bold text-neutral-600 uppercase">Country</label><input type="text" required id="f-country" value="' + esc(f.country) + '" class="w-full bg-white border border-neutral-300 rounded-lg p-2 text-xs focus:outline-none focus:border-black" /></div>' +
        '</div></div>' +
        paymentFieldsHTML +
        '<button type="submit" class="w-full bg-[#0a0a0a] hover:bg-black text-white py-4 text-xs font-bold tracking-widest uppercase rounded-lg transition-colors flex items-center justify-center space-x-2 cursor-pointer shadow-lg border border-neutral-400/40">' +
        icon('lock', 'w-4 h-4 text-white') + '<span>CONFIRM &amp; PLACE ORDER — ' + esc(formatPrice(totals.total, state.currency)) + '</span></button>' +
        '</div>' +
        '<div class="lg:col-span-5 p-5 sm:p-8 bg-neutral-50 border-l border-neutral-200 flex flex-col justify-between">' +
        '<div><h3 class="font-serif-heading text-base font-bold text-neutral-900 mb-4 pb-2 border-b border-neutral-300">Order Items (' + items.reduce(function (a, b) { return a + b.quantity; }, 0) + ')</h3>' +
        '<div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">' + itemsSummary + '</div>' +
        '<div class="mt-6 pt-4 border-t border-neutral-300 space-y-2 text-xs text-neutral-600">' +
        '<div class="flex justify-between"><span>Subtotal</span><span class="font-semibold text-neutral-900">' + esc(formatPrice(totals.rawSubtotal, state.currency)) + '</span></div>' +
        (totals.discountAmount > 0 ? '<div class="flex justify-between text-neutral-800 font-semibold"><span>Applied Discount</span><span>-' + esc(formatPrice(totals.discountAmount, state.currency)) + '</span></div>' : '') +
        '<div class="flex justify-between"><span>Delivery Fee</span><span class="font-semibold text-neutral-900">' + (totals.shipping === 0 ? 'FREE' : esc(formatPrice(totals.shipping, state.currency))) + '</span></div>' +
        '<div class="flex justify-between text-base font-bold text-[#0a0a0a] pt-3 border-t border-neutral-300"><span>Total Payable</span><span class="text-[#8b1c1c] font-bold">' + esc(formatPrice(totals.total, state.currency)) + '</span></div>' +
        '</div></div>' +
        '<div class="mt-6 p-3 bg-white border border-neutral-300 rounded-lg text-[11px] text-neutral-600 space-y-1">' +
        '<p class="flex items-center gap-1 font-bold text-neutral-900">' + icon('shieldCheck', 'w-4 h-4 text-neutral-700') + 'Pentagon Authenticity Guarantee</p>' +
        '<p>100% Genuine, verified products with full warranty and express delivery.</p></div>' +
        '</div></form>';
    } else {
      var o = checkoutState.order;
      bodyHTML =
        '<div class="p-8 sm:p-12 text-center space-y-6 max-w-2xl mx-auto animate-fade-in">' +
        '<div class="w-16 h-16 bg-neutral-100 text-neutral-800 rounded-full flex items-center justify-center mx-auto border-2 border-neutral-500">' + icon('checkCircle', 'w-10 h-10 text-neutral-700') + '</div>' +
        '<div class="space-y-2"><span class="text-xs font-bold uppercase tracking-widest text-neutral-700">Order Received &amp; Verified</span>' +
        '<h2 class="font-serif-heading text-2xl sm:text-3xl font-bold text-neutral-900 uppercase">THANK YOU FOR YOUR PURCHASE</h2>' +
        '<p class="text-xs text-neutral-600">A confirmation SMS and receipt has been sent to <strong class="text-neutral-900">' + esc(o.customer.email) + '</strong></p></div>' +
        '<div class="bg-white p-6 border border-neutral-300 rounded-xl text-left space-y-4 text-xs shadow-xs">' +
        '<div class="flex justify-between border-b pb-3 border-neutral-200 font-mono">' +
        '<div><span class="text-neutral-400 block text-[10px] uppercase">Order Ref</span><span class="font-bold text-black text-sm">' + esc(o.orderId) + '</span></div>' +
        '<div class="text-right"><span class="text-neutral-400 block text-[10px] uppercase">Date</span><span class="font-bold text-black">' + esc(o.date) + '</span></div></div>' +
        '<div><p class="font-bold uppercase text-neutral-500 text-[10px]">Delivery Address</p>' +
        '<p class="text-neutral-800 mt-0.5 font-medium">' + esc(o.customer.firstName) + ' ' + esc(o.customer.lastName) + '<br />' + esc(o.customer.address) + ', ' + esc(o.customer.city) + '<br />' + esc(o.customer.country) + '</p></div>' +
        '<div class="border-t pt-3 border-neutral-200 flex justify-between font-bold text-neutral-900 text-sm"><span>Total Amount Paid</span><span class="text-[#8b1c1c] font-bold text-base">' + esc(formatPrice(o.total, state.currency)) + '</span></div>' +
        '</div>' +
        '<div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 text-xs text-neutral-600 text-left">' +
        '<p class="font-bold text-neutral-800 uppercase tracking-wider text-[10px] mb-1">Track this order anytime</p>' +
        '<p>Sign in at <a href="' + siteUrl('account/login') + '" class="text-neutral-800 font-semibold hover:underline">My Account</a> using ' + (o.customer.email ? 'the email <strong>' + esc(o.customer.email) + '</strong>' : 'the phone number <strong>' + esc(o.customer.phone) + '</strong>') + ' you just used — no password needed.</p>' +
        '</div>' +
        '<div class="flex flex-col sm:flex-row gap-3 justify-center">' +
        '<button data-close-checkout class="bg-[#0a0a0a] hover:bg-black text-white py-3.5 px-8 text-xs font-bold tracking-widest uppercase rounded-lg transition-colors border border-neutral-400/30 cursor-pointer">CONTINUE SHOPPING</button>' +
        '<a href="' + siteUrl('track-order') + '" class="inline-flex items-center justify-center bg-white hover:bg-neutral-50 text-[#0a0a0a] py-3.5 px-8 text-xs font-bold tracking-widest uppercase rounded-lg transition-colors border-2 border-[#0a0a0a]">TRACK MY ORDER</a>' +
        '</div>' +
        '</div>';
    }

    root.innerHTML =
      '<div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">' +
      '<div class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity" ' + (checkoutState.step === 'confirmation' ? 'data-close-checkout' : '') + '></div>' +
      '<div class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl z-10 overflow-hidden border border-neutral-300 my-6">' +
      '<div class="p-4 sm:p-5 bg-black text-white border-b border-neutral-200 flex items-center justify-between">' +
      '<div class="flex items-center gap-2"><div class="w-7 h-7 bg-[#0a0a0a] text-white flex items-center justify-center rounded-md border border-neutral-400/30"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4"><polygon points="12,2 22,9 18,21 6,21 2,9" /></svg></div>' +
      '<span class="font-serif-heading text-lg font-bold tracking-widest text-white uppercase">PENTAGON CHECKOUT</span></div>' +
      '<button data-close-checkout class="p-2 text-neutral-200 hover:text-white cursor-pointer">' + icon('x', 'w-5 h-5') + '</button></div>' +
      bodyHTML +
      '</div></div>';

    root.querySelectorAll('[data-close-checkout]').forEach(function (el) { el.addEventListener('click', closeCheckoutModal); });

    if (checkoutState.step === 'details') {
      root.querySelectorAll('[data-payment-method]').forEach(function (el) {
        el.addEventListener('click', function () { f.paymentMethod = el.getAttribute('data-payment-method'); renderCheckoutModal(); });
      });
      ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'postalCode', 'country', 'mpesaPhone', 'cardNumber', 'expDate', 'cvv'].forEach(function (key) {
        var input = document.getElementById('f-' + key);
        if (input) input.addEventListener('input', function (e) { f[key] = e.target.value; });
      });
      var form = document.getElementById('checkout-form');
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var t = checkoutTotals();
        var paymentMethod = f.paymentMethod === 'mpesa' ? ('M-Pesa (' + f.mpesaPhone + ')') : 'Credit Card (*4242)';
        var customer = { firstName: f.firstName, lastName: f.lastName, email: f.email, phone: f.phone, address: f.address, city: f.city, postalCode: f.postalCode, country: f.country };

        checkoutState.step = 'processing';
        renderCheckoutModal();

        fetch(siteUrl('api/place-order'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            items: items.map(function (item) {
              return { productCode: item.productId, colorName: item.selectedColor.name, sizeLabel: item.selectedSize, quantity: item.quantity };
            }),
            currency: state.currency,
            discountPercent: state.appliedDiscountPercent,
            customer: customer,
            paymentMethod: paymentMethod,
          }),
        })
          .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
          .then(function (result) {
            if (!checkoutState) return;
            if (!result.ok || !result.data.success) {
              checkoutState.step = 'details';
              checkoutState.error = (result.data && result.data.error) || 'We could not process your order. Please try again.';
              renderCheckoutModal();
              return;
            }
            checkoutState.order = {
              orderId: result.data.orderRef,
              date: result.data.date,
              total: t.total,
              customer: customer,
            };
            checkoutState.step = 'confirmation';
            state.cart = [];
            cartUiState = { promoCode: '', discountPercent: 0, promoError: '', promoSuccess: '' };
            updateHeaderUI();
            renderCheckoutModal();
          })
          .catch(function () {
            if (!checkoutState) return;
            checkoutState.step = 'details';
            checkoutState.error = 'Network error — please check your connection and try again.';
            renderCheckoutModal();
          });
      });
    }
  }

  function logoLoaderHTML(message) {
    return '<div class="flex flex-col items-center justify-center p-6 text-center select-none">' +
      '<div class="relative flex items-center justify-center mb-4">' +
      '<div class="absolute w-16 h-16 sm:w-20 sm:h-20 bg-neutral-700/20 rounded-full blur-xl animate-pulse"></div>' +
      '<div class="relative w-12 h-12 sm:w-16 sm:h-16 bg-[#0a0a0a] text-white flex items-center justify-center rounded-xl border border-neutral-400/50 shadow-2xl animate-logo-fade">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-6 h-6 sm:w-8 sm:h-8 text-white drop-shadow-sm"><polygon points="12,2 22,9 18,21 6,21 2,9" /></svg></div></div>' +
      '<div class="space-y-1 animate-pulse"><span class="block font-serif-heading font-extrabold text-sm sm:text-base tracking-[0.25em] text-[#0a0a0a] uppercase">PENTAGON</span>' +
      '<span class="block text-[9px] sm:text-[10px] tracking-[0.35em] text-neutral-700 font-sans font-bold uppercase">COLLECTIONS</span></div>' +
      (message ? '<p class="mt-3 text-xs text-neutral-500 font-medium tracking-wider uppercase animate-fade-in">' + esc(message) + '</p>' : '') +
      '</div>';
  }

  // ---------------------------------------------------------------------
  // Size Guide — mirrors src/components/SizeGuideModal.tsx (static, pre-rendered by PHP)
  // ---------------------------------------------------------------------
  function openSizeGuide() {
    var modal = document.getElementById('size-guide-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    openModalStack.push(closeSizeGuide);
  }
  function closeSizeGuide() {
    var modal = document.getElementById('size-guide-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    popModal(closeSizeGuide);
  }
  function initSizeGuide() {
    document.querySelectorAll('[data-close-size-guide]').forEach(function (el) { el.addEventListener('click', closeSizeGuide); });
  }

  // ---------------------------------------------------------------------
  // Lookbook — mirrors src/components/LookbookSection.tsx
  // ---------------------------------------------------------------------
  function lookbookMainHTML(look) {
    var hotspots = look.products.map(function (item) {
      return '<div class="absolute transform -translate-x-1/2 -translate-y-1/2 group z-20 cursor-pointer" style="left:' + item.xPercent + '%;top:' + item.yPercent + '%" data-quickview-trigger data-product-id="' + esc(item.product.id) + '">' +
        '<div class="relative"><div class="w-7 h-7 bg-white/90 text-black rounded-full flex items-center justify-center font-bold text-xs shadow-xl animate-pulse">+</div>' +
        '<div class="absolute left-full top-1/2 -translate-y-1/2 ml-3 hidden group-hover:flex items-center bg-white text-black p-2.5 rounded-xs shadow-2xl w-52 z-30 border border-neutral-200 animate-fade-in">' +
        '<img src="' + esc(imgUrl(item.product.images[0])) + '" alt="" class="w-10 h-12 object-cover rounded-xs mr-2" />' +
        '<div class="flex-1 min-w-0"><p class="font-serif-heading font-bold text-xs text-neutral-900 truncate">' + esc(item.product.name) + '</p>' +
        '<p class="text-[10px] text-neutral-800 font-bold mt-0.5">$' + item.product.price + '</p>' +
        '<span class="text-[9px] uppercase tracking-wider text-neutral-400 font-semibold block mt-0.5">Shop This Piece →</span></div></div></div></div>';
    }).join('');
    return '<img src="' + esc(imgUrl(look.mainImage)) + '" alt="' + esc(look.title) + '" class="w-full h-full object-cover object-center filter brightness-95" />' +
      '<div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>' +
      hotspots +
      '<div class="absolute bottom-6 left-6 max-w-lg text-left"><span class="text-xs font-mono text-white tracking-widest uppercase block mb-1">' + esc(look.subtitle) + '</span>' +
      '<h3 class="font-serif-heading text-2xl sm:text-3xl font-bold text-white">' + esc(look.title) + '</h3></div>';
  }

  function lookbookListHTML(look) {
    return look.products.map(function (item) {
      var p = item.product;
      return '<div data-quickview-trigger data-product-id="' + esc(p.id) + '" class="flex items-center space-x-3 p-3 bg-neutral-900/90 border border-neutral-800 rounded-xs hover:border-neutral-300 transition-colors cursor-pointer group">' +
        '<img src="' + esc(imgUrl(p.images[0])) + '" alt="' + esc(p.name) + '" class="w-16 h-20 object-cover rounded-xs" />' +
        '<div class="flex-1 min-w-0"><p class="text-[10px] text-white font-mono uppercase tracking-widest">' + esc(p.subCategory) + '</p>' +
        '<h5 class="font-serif-heading text-sm font-bold text-white group-hover:text-neutral-200 truncate">' + esc(p.name) + '</h5>' +
        '<p class="text-xs font-bold text-neutral-300 mt-1">$' + p.price + '</p></div>' +
        '<div class="p-2 bg-neutral-800 group-hover:bg-neutral-200 group-hover:text-black rounded-xs transition-colors">' + icon('eye', 'w-4 h-4') + '</div></div>';
    }).join('');
  }

  function switchLookbookTab(idx) {
    state.activeLookIndex = idx;
    var look = LOOKBOOK[idx];
    document.getElementById('lookbook-main').innerHTML = lookbookMainHTML(look);
    document.getElementById('lookbook-list').innerHTML = lookbookListHTML(look);
    document.querySelectorAll('.lookbook-tab').forEach(function (btn, i) {
      var active = i === idx;
      btn.classList.toggle('bg-neutral-200', active);
      btn.classList.toggle('text-[#1a1a1a]', active);
      btn.classList.toggle('font-bold', active);
      btn.classList.toggle('bg-neutral-900', !active);
      btn.classList.toggle('text-neutral-400', !active);
      btn.classList.toggle('hover:text-white', !active);
      btn.classList.toggle('border', !active);
      btn.classList.toggle('border-neutral-800', !active);
    });
  }

  function initLookbook() {
    document.querySelectorAll('.lookbook-tab').forEach(function (btn) {
      btn.addEventListener('click', function () { switchLookbookTab(+btn.getAttribute('data-lookbook-index')); });
    });
  }

  // ---------------------------------------------------------------------
  // Newsletter — mirrors src/components/Newsletter.tsx
  // ---------------------------------------------------------------------
  function initNewsletter() {
    var form = document.getElementById('newsletter-form');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = document.getElementById('newsletter-email').value;
      if (email) {
        form.classList.add('hidden');
        var success = document.getElementById('newsletter-success');
        success.classList.remove('hidden');
        success.classList.add('flex');
      }
    });
  }

  // ---------------------------------------------------------------------
  // Modal root management + global delegated events
  // ---------------------------------------------------------------------
  function ensureModal(id) {
    var existing = document.getElementById(id);
    if (existing) return existing;
    var div = document.createElement('div');
    div.id = id;
    document.getElementById('modal-root').appendChild(div);
    return div;
  }
  function removeModal(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
  }
  function popModal(fn) {
    var i = openModalStack.indexOf(fn);
    if (i > -1) openModalStack.splice(i, 1);
  }
  function refreshOpenModals() {
    if (document.getElementById('modal-cart')) renderCartDrawer();
    if (document.getElementById('modal-wishlist')) renderWishlistDrawer();
    if (document.getElementById('modal-quickview')) renderQuickView();
    if (document.getElementById('modal-search')) renderSearchModal();
    if (document.getElementById('modal-checkout')) renderCheckoutModal();
  }

  function initGlobalDelegation() {
    document.addEventListener('click', function (e) {
      var qv = e.target.closest('[data-quickview-trigger]');
      if (qv) { openQuickView(qv.getAttribute('data-product-id')); return; }

      var wish = e.target.closest('[data-wishlist-toggle]');
      if (wish) { toggleWishlist(wish.getAttribute('data-product-id')); return; }

      var buyNow = e.target.closest('[data-buy-now]');
      if (buyNow) {
        var pid1 = buyNow.getAttribute('data-product-id');
        var p1 = findProduct(pid1);
        addToCart(pid1, defaultProductColor(p1), defaultProductSize(p1), 1);
        openCheckoutModal();
        return;
      }

      var quickAdd = e.target.closest('[data-quick-add]');
      if (quickAdd) {
        var pid2 = quickAdd.getAttribute('data-product-id');
        var p2 = findProduct(pid2);
        addToCart(pid2, defaultProductColor(p2), defaultProductSize(p2), 1);
        openCartDrawer();
        return;
      }

      var catBtn = e.target.closest('[data-select-category]');
      if (catBtn) {
        var cat = catBtn.getAttribute('data-select-category');
        selectCategory(cat);
        if (window.__closeMobileMenu) window.__closeMobileMenu();
        if (cat !== 'all') document.getElementById('products-section').scrollIntoView({ behavior: 'smooth' });
        return;
      }

      var curBtn = e.target.closest('[data-select-currency]');
      if (curBtn) { setCurrency(curBtn.getAttribute('data-select-currency')); return; }

      if (e.target.closest('#open-cart')) { openCartDrawer(); return; }
      if (e.target.closest('#open-wishlist')) { openWishlistDrawer(); return; }
      if (e.target.closest('#open-search')) { openSearchModal(); return; }
      if (e.target.closest('#footer-open-size-guide')) { openSizeGuide(); return; }
      if (e.target.closest('#footer-open-wishlist')) { openWishlistDrawer(); return; }
    });
  }

  // ---------------------------------------------------------------------
  // Boot
  // ---------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    initHeader();
    setActiveNav(state.currentCategory);
    if (document.getElementById('hero-data')) initHero();
    initOffersHero();
    initProductSection();
    if (document.getElementById('lookbook-section')) initLookbook();
    if (document.getElementById('newsletter-form')) initNewsletter();
    initSizeGuide();
    initGlobalDelegation();
    trapEscape();
  });
})();
