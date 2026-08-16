<?php
/** Requires $products, $categories, $occasions, $lookbook, $reviews, $currency, $focus in scope. */
?>
    <!-- Dynamic modals/drawers (QuickView, Cart, Wishlist, Search, Checkout) are
         injected here by assets/js/app.js, mirroring conditional rendering —
         nothing renders until opened. -->
    <div id="modal-root"></div>
  </div>

  <script id="pentagon-data" type="application/json">
    <?= json_encode([
        'products' => $products,
        'categories' => $categories,
        'occasions' => $occasions ?? [],
        'lookbook' => $lookbook,
        'reviews' => $reviews,
        'currency' => $currency,
        'baseUrl' => url('/'),
        'focusProductCode' => $focus['product'] ?? null,
        'focusCategoryKey' => $focus['category'] ?? null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
  <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
