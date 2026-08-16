<?php

namespace App\Controllers;

use App\Core\View;
use App\Core\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\SeoMeta;

class StorefrontController
{
    public function home(): void
    {
        $seo = SeoMeta::resolve(
            'home',
            'Pentagon Collections | Minimalist Luxury Apparel & Style',
            'Authentic luxury collections: gold dining sets, transparent Sakura umbrellas, Italian wool trenches, silk dresses and Mongolian cashmere.'
        );
        $this->renderPage($seo, ['product' => null, 'category' => null]);
    }

    public function product(string $code): void
    {
        $product = Product::findByCode($code);
        if (!$product) {
            http_response_code(404);
            View::render('storefront.404');
            return;
        }
        $seo = SeoMeta::resolve(
            'product:' . $code,
            $product['name'] . ' | Pentagon Collections',
            $product['description'] ? mb_substr($product['description'], 0, 160) : $product['subtitle'],
            $product['images'][0] ?? null
        );
        $this->renderPage($seo, ['product' => $code, 'category' => null]);
    }

    public function category(string $key): void
    {
        $category = Category::findByKey($key);
        if (!$category) {
            http_response_code(404);
            View::render('storefront.404');
            return;
        }
        $seo = SeoMeta::resolve(
            'category:' . $key,
            $category['name'] . ' | Pentagon Collections',
            $category['tagline'] ?? ('Shop ' . $category['name'] . ' at Pentagon Collections.'),
            $category['image']
        );
        $this->renderPage($seo, ['product' => null, 'category' => $key]);
    }

    public function trackOrder(): void
    {
        $ref = strtoupper(trim((string) Request::input('order_ref', '')));
        $order = null;
        $items = [];
        $searched = $ref !== '';

        if ($searched) {
            $order = Order::findByRef($ref);
            if ($order) {
                $items = OrderItem::forOrder((int) $order['id']);
            }
        }

        $seo = SeoMeta::resolve(
            'track-order',
            'Track Your Order | Pentagon Collections',
            'Track your Pentagon Collections order status using your order reference.'
        );
        $categories = Category::all();
        $products = Product::all();
        $occasions = Product::activeOccasions();

        View::render('storefront.layout-head', ['seo' => $seo]);
        View::render('storefront.header', ['categories' => $categories, 'occasions' => $occasions]);
        View::render('storefront.track-order', [
            'order' => $order,
            'items' => $items,
            'searched' => $searched,
            'orderRef' => $ref,
        ]);
        View::render('storefront.footer', ['currency' => 'KSH', 'occasions' => $occasions]);
        View::render('storefront.layout-foot', [
            'products' => $products, 'categories' => $categories, 'occasions' => $occasions, 'lookbook' => [],
            'reviews' => [], 'currency' => 'KSH', 'focus' => ['product' => null, 'category' => null],
        ]);
    }

    private function renderPage(array $seo, array $focus): void
    {
        $products = Product::all();
        $categories = Category::all();
        $occasions = Product::activeOccasions();
        $offers = array_values(array_filter($products, fn(array $product): bool => !empty($product['isSale'])));
        if (!$offers) {
            $offers = array_slice($products, 0, 4);
        }
        $lookbook = $this->lookbook();
        $reviews = $this->reviews();
        $currency = 'KSH';

        View::render('storefront.layout-head', ['seo' => $seo]);
        View::render('storefront.header', ['categories' => $categories, 'occasions' => $occasions]);
        View::render('storefront.offers-hero', [
            'categories' => $categories,
            'occasions' => $occasions,
            'offers' => $offers,
            'currency' => $currency,
        ]);
        View::render('storefront.product-section', ['products' => $products, 'currency' => $currency]);
        View::render('storefront.footer', ['currency' => $currency, 'occasions' => $occasions]);
        View::render('storefront.size-guide-modal');
        View::render('storefront.layout-foot', [
            'products' => $products, 'categories' => $categories, 'occasions' => $occasions, 'lookbook' => $lookbook,
            'reviews' => $reviews, 'currency' => $currency, 'focus' => $focus,
        ]);
    }

    /**
     * The two featured pieces per look are pinned to stable product codes
     * (rather than array position) so admin edits/adds/deletes of other
     * products never shift which item shows up in a lookbook.
     */
    private function lookbook(): array
    {
        $config = [
            [
                'id' => 'look-1', 'title' => 'The Monolithic Silhouettes', 'subtitle' => 'Autumn / Winter Architecture',
                'season' => 'VOL. 04 — AW26', 'mainImage' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1400&q=80',
                'products' => [
                    ['code' => 'pentagon-001', 'xPercent' => 35, 'yPercent' => 40],
                    ['code' => 'pentagon-006', 'xPercent' => 70, 'yPercent' => 65],
                ],
            ],
            [
                'id' => 'look-2', 'title' => 'Muted Harmony & Soft Tailoring', 'subtitle' => 'The Suiting Edition',
                'season' => 'VOL. 05 — EDITORIAL', 'mainImage' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1400&q=80',
                'products' => [
                    ['code' => 'pentagon-004', 'xPercent' => 45, 'yPercent' => 35],
                    ['code' => 'pentagon-005', 'xPercent' => 55, 'yPercent' => 75],
                ],
            ],
        ];

        $resolved = [];
        foreach ($config as $look) {
            $products = [];
            foreach ($look['products'] as $item) {
                $product = Product::findByCode($item['code']);
                if ($product) {
                    $products[] = ['product' => $product, 'xPercent' => $item['xPercent'], 'yPercent' => $item['yPercent']];
                }
            }
            if ($products) {
                $look['products'] = $products;
                $resolved[] = $look;
            }
        }
        return $resolved;
    }

    private function reviews(): array
    {
        return [
            [
                'id' => 'rev-1', 'author' => 'Eleanor Vance', 'location' => 'London, UK', 'rating' => 5,
                'title' => 'Flawless tailoring and drape',
                'comment' => 'The Atelier Trench coat surpassed every expectation. The wool weight feels ultra-luxurious and hangs effortlessly. Pentagon Collections is my new go-to for capsule wardrobe pieces.',
                'date' => '3 days ago', 'productImage' => 'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'id' => 'rev-2', 'author' => 'Marcus Sterling', 'location' => 'New York, NY', 'rating' => 5,
                'title' => 'Subtle luxury at its finest',
                'comment' => 'The cashmere quality is right up there with Savile Row fashion houses. Minimalist branding, incredible soft texture, and fast express delivery.',
                'date' => '1 week ago', 'productImage' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'id' => 'rev-3', 'author' => 'Sophia Chen', 'location' => 'Paris, France', 'rating' => 5,
                'title' => 'Ideal bias cut silk dress',
                'comment' => "Wore the Satin Bias-Cut midi to an evening gala in Paris. The champagne hue has a rich lustre that photos don't even do justice to.",
                'date' => '2 weeks ago', 'productImage' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=400&q=80',
            ],
        ];
    }
}
