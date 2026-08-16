<?php
/**
 * Offers hero and side category rail.
 * Requires $categories, $occasions, $offers, $currency in scope.
 */
$activeOffers = array_values($offers ?? []);
$categoryList = array_values($categories ?? []);
$occasionList = array_values($occasions ?? []);
?>
<section class="store-market-shell" aria-label="Featured offers">
  <aside class="store-side-nav" aria-label="Categories">
    <div class="store-side-nav-card">
      <h2>Categories</h2>
      <button data-select-category="all" class="store-side-category is-active" data-nav-id="all">
        <span class="store-side-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </span>
        <span>All Categories</span>
        <span class="store-side-arrow">›</span>
      </button>
      <?php foreach ($categoryList as $category): ?>
        <button data-select-category="<?= e($category['id']) ?>" class="store-side-category" data-nav-id="<?= e($category['id']) ?>">
          <span class="store-side-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/></svg>
          </span>
          <span><?= e($category['name']) ?></span>
          <span class="store-side-arrow">›</span>
        </button>
      <?php endforeach; ?>
      <?php if ($occasionList): ?>
        <div class="store-side-divider"></div>
        <?php foreach (array_slice($occasionList, 0, 5) as $occasion): ?>
          <button data-select-category="<?= e($occasion['id']) ?>" class="store-side-category" data-nav-id="<?= e($occasion['id']) ?>">
            <span class="store-side-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>
            </span>
            <span><?= e($occasion['label']) ?></span>
            <span class="store-side-arrow">›</span>
          </button>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="store-side-promo">
      <h3>Store-Only Deals</h3>
      <p>Browse live discounts from the products your admin team has marked on offer.</p>
      <button data-select-category="new">New Arrivals</button>
    </div>
  </aside>

  <div class="store-offers-area">
    <div class="store-offer-carousel" id="offer-carousel">
      <?php foreach ($activeOffers as $idx => $offer):
        $image = imageUrl($offer['offerImage'] ?? ($offer['images'][0] ?? ''));
        $original = $offer['originalPrice'] ?? null;
        $discount = $original && $original > $offer['price'] ? max(1, (int) round((1 - ($offer['price'] / $original)) * 100)) : null;
      ?>
        <article class="store-offer-slide store-offer-theme-<?= $idx % 5 ?> <?= $idx === 0 ? 'is-active' : '' ?>" data-offer-slide="<?= $idx ?>">
          <div class="store-offer-copy">
            <span class="store-offer-kicker"><?= $discount ? e($discount . '% OFF') : 'Featured Offer' ?></span>
            <h1><?= e($offer['name']) ?></h1>
            <p><?= e($offer['subtitle'] ?: ($offer['description'] ?? 'Limited-time storefront offer.')) ?></p>
            <div class="store-offer-price-row">
              <span><?= e(formatPrice((float) $offer['price'], $currency)) ?></span>
              <?php if ($original): ?><del><?= e(formatPrice((float) $original, $currency)) ?></del><?php endif; ?>
            </div>
            <div class="store-offer-actions">
              <button data-buy-now data-product-id="<?= e($offer['id']) ?>">Buy Now</button>
              <button data-quickview-trigger data-product-id="<?= e($offer['id']) ?>">View Details</button>
            </div>
          </div>
          <div class="store-offer-media">
            <?php if ($image): ?>
              <img src="<?= e($image) ?>" alt="<?= e($offer['name']) ?>" />
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if (count($activeOffers) > 1): ?>
        <button class="store-offer-arrow store-offer-arrow-prev" type="button" data-offer-prev aria-label="Previous offer">‹</button>
        <button class="store-offer-arrow store-offer-arrow-next" type="button" data-offer-next aria-label="Next offer">›</button>
        <div class="store-offer-dots" aria-label="Offer slides">
          <?php foreach ($activeOffers as $idx => $_): ?>
            <button type="button" data-offer-dot="<?= $idx ?>" class="<?= $idx === 0 ? 'is-active' : '' ?>" aria-label="Show offer <?= $idx + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="store-offer-strip" aria-label="Current offer products">
      <?php foreach (array_slice($activeOffers, 0, 4) as $offer):
        $image = imageUrl($offer['offerImage'] ?? ($offer['images'][0] ?? ''));
      ?>
        <button data-quickview-trigger data-product-id="<?= e($offer['id']) ?>" class="store-offer-mini">
          <?php if ($image): ?><img src="<?= e($image) ?>" alt="" /><?php endif; ?>
          <span><?= e($offer['name']) ?></span>
          <strong><?= e(formatPrice((float) $offer['price'], $currency)) ?></strong>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
