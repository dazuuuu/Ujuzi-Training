<?php
/**
 * Mirrors the product catalog section of src/App.tsx (category filter, sort,
 * and product grid). Initial render = category 'all', sort 'featured'.
 * assets/js/app.js re-renders #product-grid + #products-heading on filter/sort/currency changes.
 * Requires $products, $currency in scope.
 */
require_once __DIR__ . '/partials/product-card.php';
?>
<section id="products-section" class="store-products-section">
  <div class="store-products-heading-row">
    <div>
      <h2 id="products-heading" class="store-products-heading">Recommended for You</h2>
      <p id="products-count" class="store-products-count"><?= count($products) ?> items found</p>
    </div>
    <select id="sort-select" class="store-sort-select" aria-label="Sort products">
      <option value="featured">Featured First</option>
      <option value="price-low">Price: Low to High</option>
      <option value="price-high">Price: High to Low</option>
      <option value="rating">Highest Rated</option>
    </select>
  </div>

  <div id="product-grid" class="store-product-grid">
    <?php foreach ($products as $product): ?>
      <?= renderProductCard($product, $currency, false) ?>
    <?php endforeach; ?>
  </div>
</section>
