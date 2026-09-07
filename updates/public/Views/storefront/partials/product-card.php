<?php
/**
 * Mirrors src/components/ProductCard.tsx
 * Hover zoom / image swap / wishlist toggle / add-to-cart / buy-now handled
 * client-side in assets/js/app.js (delegated listeners keyed off data-* attrs).
 */

if (!function_exists('renderProductCard')) {
function renderProductCard(array $product, string $currency, bool $isWishlisted): string
{
    $formattedPrice = formatPrice($product['price'], $currency);
    $formattedOriginal = isset($product['originalPrice']) ? formatPrice($product['originalPrice'], $currency) : null;
    $images = array_values(array_filter($product['images'] ?? []));
    $fallbackImage = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20viewBox%3D%220%200%20800%201000%22%3E%3Crect%20width%3D%22800%22%20height%3D%221000%22%20fill%3D%22%23f5f5f5%22/%3E%3Cpath%20d%3D%22M260%20470h280v60H260z%22%20fill%3D%22%23d4d4d4%22/%3E%3Cpath%20d%3D%22M310%20380h180v60H310z%22%20fill%3D%22%23e5e5e5%22/%3E%3Ctext%20x%3D%22400%22%20y%3D%22580%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2232%22%20fill%3D%22%23737373%22%20text-anchor%3D%22middle%22%3ENo%20image%3C/text%3E%3C/svg%3E';
    $img0 = e(imageUrl($images[0] ?? $fallbackImage));
    $img1 = e(imageUrl($images[1] ?? ($images[0] ?? $fallbackImage)));
    $wishClasses = $isWishlisted ? 'is-active' : '';
    $heartFill = $isWishlisted ? 'currentColor' : 'none';

    ob_start();
    ?>
    <div class="product-card group" data-product-id="<?= e($product['id']) ?>">
      <div>
        <div class="product-card-image-wrap" data-quickview-trigger data-product-id="<?= e($product['id']) ?>">
          <img src="<?= $img0 ?>" data-primary="<?= $img0 ?>" data-secondary="<?= $img1 ?>" alt="<?= e($product['name']) ?>" class="product-card-img w-full h-full object-cover object-center transition-transform duration-200 ease-out pointer-events-none scale-100" />

          <div class="product-card-badges">
            <?php if (!empty($product['isNew'])): ?>
              <span>NEW</span>
            <?php endif; ?>
            <?php if (!empty($product['isBestSeller'])): ?>
              <span>BEST SELLER</span>
            <?php endif; ?>
          </div>

          <div class="product-card-quickview">
            <span class="product-card-zoom-label hidden">Quick View</span>
            <button data-quickview-trigger data-product-id="<?= e($product['id']) ?>" title="Quick View Details">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="product-card-body">
          <div class="product-card-title-row">
            <h3 data-quickview-trigger data-product-id="<?= e($product['id']) ?>"><?= e($product['name']) ?></h3>
            <button data-wishlist-toggle data-product-id="<?= e($product['id']) ?>" class="wishlist-btn <?= $wishClasses ?>" aria-label="Wishlist">
              <svg viewBox="0 0 24 24" fill="<?= $heartFill ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
            </button>
          </div>

          <div class="product-card-price-row">
            <span class="product-card-price"><?= e($formattedPrice) ?></span>
            <?php if ($formattedOriginal): ?>
              <span class="product-card-original-price"><?= e($formattedOriginal) ?></span>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <div class="product-card-actions">
        <button data-buy-now data-product-id="<?= e($product['id']) ?>" class="product-buy-btn">
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
          <span>BUY NOW</span>
        </button>
        <button data-quick-add data-product-id="<?= e($product['id']) ?>" class="product-cart-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
          <span>ADD TO CART</span>
        </button>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
}
