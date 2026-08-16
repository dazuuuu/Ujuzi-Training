<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Services\UploadException;
use App\Services\UploadService;

class ProductController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.products.index', [
            'pageTitle' => 'Products',
            'activeNav' => 'products',
            'products' => Product::search(trim((string) Request::query('q', ''))),
            'search' => trim((string) Request::query('q', '')),
        ]);
    }

    public function create(): void
    {
        $this->showForm(null, [], $this->emptyForm(), []);
    }

    public function edit(string $id): void
    {
        $product = Product::findRawById((int) $id);
        if (!$product) {
            redirect('/admin/products');
        }
        $form = [
            'name' => $product['name'] ?? '',
            'base_price' => $product['original_price'] ?? $product['price'] ?? '',
            'price' => $product['price'] ?? '',
            'original_price' => $product['original_price'] ?? '', 'category_key' => $product['category_key'] ?? '',
            'category_keys' => $this->selectedCategoryKeys($product),
            'description' => $product['description'] ?? '',
            'sizes' => implode("\n", json_decode($product['sizes'] ?? '[]', true) ?: []),
            'has_offer' => !empty($product['is_sale']) || !empty($product['original_price']) ? '1' : '',
            'offer_type' => 'price',
            'offer_value' => !empty($product['is_sale']) || !empty($product['original_price']) ? ($product['price'] ?? '') : '',
            'colors' => json_decode($product['colors'] ?? '[]', true) ?: [],
        ];
        $images = json_decode($product['images'] ?? '[]', true) ?: [];
        $this->showForm($product, [], $form, $images);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        $product = Product::findRawById((int) $id);
        if (!$product) {
            redirect('/admin/products');
        }
        $this->save($product);
    }

    public function destroy(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            $product = Product::findRawById((int) $id);
            if ($product) {
                foreach (json_decode($product['images'] ?? '[]', true) ?: [] as $img) {
                    UploadService::delete($img);
                }
                foreach (json_decode($product['colors'] ?? '[]', true) ?: [] as $c) {
                    if (!empty($c['image'])) {
                        UploadService::delete($c['image']);
                    }
                }
                Product::delete((int) $id);
                flashSuccess('Product deleted.');
            }
        }
        redirect('/admin/products');
    }

    private function emptyForm(): array
    {
        return [
            'name' => '', 'price' => '', 'original_price' => '', 'category_key' => '',
            'category_keys' => [],
            'description' => '', 'sizes' => '',
            'base_price' => '',
            'has_offer' => '',
            'offer_type' => 'price',
            'offer_value' => '',
            'colors' => [],
        ];
    }

    private function save(?array $product): void
    {
        $errors = [];
        if (!csrfVerify(Request::post('csrf_token'))) {
            $errors[] = 'Your session expired. Please resubmit the form.';
        }

        $form = [];
        foreach (['name', 'description'] as $f) {
            $form[$f] = trim((string) Request::post($f, ''));
        }
        $form['subtitle'] = '';
        $form['sub_category'] = '';
        $form['occasion'] = '';
        $form['fabric'] = '';
        $form['fit'] = '';
        $form['base_price'] = (float) Request::post('base_price', Request::post('price', 0));
        $form['has_offer'] = Request::post('has_offer') ? '1' : '';
        $form['offer_type'] = Request::post('offer_type', 'price') === 'percentage' ? 'percentage' : 'price';
        $form['offer_value'] = trim((string) Request::post('offer_value', ''));
        $form['price'] = $form['base_price'];
        $form['original_price'] = null;
        $form['category_keys'] = $this->validCategoryKeys(Request::post('category_keys', []));
        $form['category_key'] = $form['category_keys'][0] ?? '';
        $form['sizes'] = trim((string) Request::post('sizes', ''));
        $form['details'] = '';
        $form['is_new'] = $product ? (int) ($product['is_new'] ?? 1) : 1;
        $form['is_best_seller'] = $product ? (int) ($product['is_best_seller'] ?? 0) : 0;
        $form['is_sale'] = $product ? (int) ($product['is_sale'] ?? 0) : 0;
        $form['in_stock'] = $product ? (int) ($product['in_stock'] ?? 1) : 1;
        $form['featured_in_lookbook'] = $product ? (int) ($product['featured_in_lookbook'] ?? 0) : 0;
        $form['rating'] = $product ? (float) ($product['rating'] ?? 5.0) : 5.0;
        $form['review_count'] = $product ? (int) ($product['review_count'] ?? 0) : 0;

        if ($form['name'] === '') $errors[] = 'Product name is required.';
        if ($form['base_price'] <= 0) $errors[] = 'Base price must be greater than 0.';
        if (!$form['category_keys']) $errors[] = 'Select at least one category.';
        if ($form['has_offer'] === '1') {
            $offerValue = (float) $form['offer_value'];
            if ($offerValue <= 0) {
                $errors[] = 'Offer price or percentage must be greater than 0.';
            } elseif ($form['offer_type'] === 'percentage') {
                if ($offerValue >= 100) {
                    $errors[] = 'Offer percentage must be less than 100.';
                } else {
                    $form['price'] = round($form['base_price'] * (1 - ($offerValue / 100)), 2);
                    $form['original_price'] = $form['base_price'];
                }
            } elseif ($offerValue >= $form['base_price']) {
                $errors[] = 'Offer price must be less than the base price.';
            } else {
                $form['price'] = $offerValue;
                $form['original_price'] = $form['base_price'];
            }
        }

        $existingImages = $product ? (json_decode($product['images'] ?? '[]', true) ?: []) : [];
        $originalCover = $existingImages[0] ?? null;
        $removeImages = Request::post('images_remove', []);
        $images = array_values(array_filter($existingImages, fn($img) => !in_array($img, $removeImages, true)));
        foreach ($removeImages as $rm) {
            if (in_array($rm, $existingImages, true)) {
                UploadService::delete($rm);
            }
        }
        $coverFile = Request::file('cover_image');
        if ($coverFile && ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $newCover = UploadService::store($coverFile, 'products');
                if ($originalCover && in_array($originalCover, $images, true)) {
                    UploadService::delete($originalCover);
                    $images = array_values(array_filter($images, fn($img) => $img !== $originalCover));
                }
                array_unshift($images, $newCover);
                $images = array_values(array_unique($images));
            } catch (UploadException $e) {
                $errors[] = 'Cover image upload: ' . $e->getMessage();
            }
        }

        $newImageFiles = $_FILES['gallery_images'] ?? ($_FILES['images'] ?? null);
        if ($newImageFiles && !empty($newImageFiles['name'][0])) {
            foreach ($newImageFiles['name'] as $i => $name) {
                if ($name === '') continue;
                try {
                    $images[] = UploadService::store([
                        'name' => $newImageFiles['name'][$i], 'type' => $newImageFiles['type'][$i],
                        'tmp_name' => $newImageFiles['tmp_name'][$i], 'error' => $newImageFiles['error'][$i], 'size' => $newImageFiles['size'][$i],
                    ], 'products');
                } catch (UploadException $e) {
                    $errors[] = 'Photo upload: ' . $e->getMessage();
                }
            }
        }
        if (!$images) {
            $errors[] = 'Add a cover image.';
        }

        $sizes = array_values(array_filter(array_map('trim', explode("\n", $form['sizes']))));
        $colors = $this->parseColors(Request::post('color_names', []), Request::post('color_hexes', []));
        $form['colors'] = $colors;

        if (!$errors) {
            $data = [
                'name' => $form['name'],
                'subtitle' => null,
                'price' => $form['price'],
                'original_price' => $form['original_price'],
                'category_key' => $form['category_key'],
                'category_keys' => json_encode($form['category_keys'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'sub_category' => null,
                'description' => $form['description'] ?: null,
                'sizes' => json_encode($sizes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'colors' => json_encode($colors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'images' => json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'is_sale' => $form['has_offer'] === '1' ? 1 : 0,
            ];

            try {
                if ($product) {
                    Product::update((int) $product['id'], $data);
                } else {
                    Product::create($data);
                }
            } catch (\Throwable $e) {
                $errors[] = 'Product could not be saved. ' . $e->getMessage();
            }

            if (!$errors) {
                flashSuccess($product ? 'Product updated.' : 'Product created.');
                redirect('/admin/products');
            }
        }

        $this->showForm($product, $errors, $form, $images);
    }

    private function selectedCategoryKeys(?array $product): array
    {
        if (!$product) {
            return [];
        }

        $keys = json_decode($product['category_keys'] ?? '[]', true);
        if (!is_array($keys) || !$keys) {
            $keys = !empty($product['category_key']) ? [(string) $product['category_key']] : [];
        }

        return $this->validCategoryKeys($keys);
    }

    private function validCategoryKeys($keys): array
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $valid = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            if ($key !== '' && Category::keyExists($key)) {
                $valid[] = $key;
            }
        }

        return array_values(array_unique($valid));
    }

    private function parseColors($names, $hexes): array
    {
        if (!is_array($names)) {
            $names = [];
        }
        if (!is_array($hexes)) {
            $hexes = [];
        }

        $colors = [];
        foreach ($hexes as $i => $hex) {
            $hex = strtolower(trim((string) $hex));
            $name = trim((string) ($names[$i] ?? ''));
            if ($hex === '' && $name === '') {
                continue;
            }
            if (!preg_match('/^#[0-9a-f]{6}$/', $hex)) {
                continue;
            }
            $colors[] = [
                'name' => $name !== '' ? $name : strtoupper($hex),
                'hex' => $hex,
                'image' => null,
            ];
        }

        return array_values($colors);
    }

    private function showForm(?array $product, array $errors, array $form, array $images): void
    {
        View::render('admin.products.form', [
            'pageTitle' => $product ? 'Edit Product' : 'Add Product',
            'activeNav' => 'product-form',
            'product' => $product,
            'errors' => $errors,
            'form' => $form,
            'existingImages' => $images,
            'categories' => Category::allWithCounts(),
        ]);
    }
}
