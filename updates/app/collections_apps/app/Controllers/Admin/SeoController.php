<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Services\UploadException;
use App\Services\UploadService;

class SeoController extends BaseAdminController
{
    public function index(): void
    {
        $existing = SeoMeta::all();

        $pages = [['key' => 'home', 'label' => 'Home Page', 'type' => 'Storefront']];
        foreach (Category::all() as $cat) {
            $pages[] = ['key' => 'category:' . $cat['id'], 'label' => $cat['name'], 'type' => 'Category'];
        }
        foreach (Product::all() as $product) {
            $pages[] = ['key' => 'product:' . $product['id'], 'label' => $product['name'], 'type' => 'Product'];
        }

        foreach ($pages as &$page) {
            $page['hasCustomSeo'] = isset($existing[$page['key']]);
        }

        View::render('admin.seo.index', [
            'pageTitle' => 'SEO',
            'activeNav' => 'seo',
            'pages' => $pages,
        ]);
    }

    public function edit(string $pageKey): void
    {
        [$label, $fallbackTitle, $fallbackDescription, $fallbackImage, $found] = $this->resolvePage($pageKey);
        if (!$found) {
            redirect('/admin/seo');
        }

        $entry = SeoMeta::find($pageKey);
        $form = [
            'meta_title' => $entry['meta_title'] ?? '',
            'meta_description' => $entry['meta_description'] ?? '',
            'meta_keywords' => $entry['meta_keywords'] ?? '',
            'tags' => $entry['tags'] ?? '',
        ];

        View::render('admin.seo.form', [
            'pageTitle' => 'SEO — ' . $label,
            'activeNav' => 'seo',
            'pageKey' => $pageKey,
            'label' => $label,
            'fallbackTitle' => $fallbackTitle,
            'fallbackDescription' => $fallbackDescription,
            'featuredImage' => $entry['featured_image'] ?? $fallbackImage,
            'errors' => [],
            'form' => $form,
        ]);
    }

    public function update(string $pageKey): void
    {
        [$label, , , $fallbackImage, $found] = $this->resolvePage($pageKey);
        if (!$found) {
            redirect('/admin/seo');
        }

        $errors = [];
        if (!csrfVerify(Request::post('csrf_token'))) {
            $errors[] = 'Your session expired. Please resubmit the form.';
        }

        $entry = SeoMeta::find($pageKey);
        $featuredImage = $entry['featured_image'] ?? null;
        $file = Request::file('featured_image');
        if ($file && !empty($file['name'])) {
            try {
                $newImage = UploadService::store($file, 'products');
                if ($featuredImage) {
                    UploadService::delete($featuredImage);
                }
                $featuredImage = $newImage;
            } catch (UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            SeoMeta::upsert($pageKey, [
                'meta_title' => trim((string) Request::post('meta_title', '')),
                'meta_description' => trim((string) Request::post('meta_description', '')),
                'meta_keywords' => trim((string) Request::post('meta_keywords', '')),
                'tags' => trim((string) Request::post('tags', '')),
                'featured_image' => $featuredImage,
            ]);
            flashSuccess('SEO details saved for "' . $label . '".');
            redirect('/admin/seo');
        }

        View::render('admin.seo.form', [
            'pageTitle' => 'SEO — ' . $label,
            'activeNav' => 'seo',
            'pageKey' => $pageKey,
            'label' => $label,
            'fallbackTitle' => $label,
            'fallbackDescription' => '',
            'featuredImage' => $featuredImage ?? $fallbackImage,
            'errors' => $errors,
            'form' => [
                'meta_title' => Request::post('meta_title', ''),
                'meta_description' => Request::post('meta_description', ''),
                'meta_keywords' => Request::post('meta_keywords', ''),
                'tags' => Request::post('tags', ''),
            ],
        ]);
    }

    /** @return array{0:string,1:string,2:string,3:?string,4:bool} [label, fallbackTitle, fallbackDescription, fallbackImage, found] */
    private function resolvePage(string $pageKey): array
    {
        if ($pageKey === 'home') {
            return ['Home Page', 'Pentagon Collections | Minimalist Luxury Apparel & Style', 'Authentic luxury collections at Pentagon Collections.', null, true];
        }
        if (str_starts_with($pageKey, 'category:')) {
            $category = Category::findByKey(substr($pageKey, 9));
            if ($category) {
                return [$category['name'], $category['name'] . ' | Pentagon Collections', $category['tagline'] ?? '', $category['image'], true];
            }
        }
        if (str_starts_with($pageKey, 'product:')) {
            $product = Product::findByCode(substr($pageKey, 8));
            if ($product) {
                return [$product['name'], $product['name'] . ' | Pentagon Collections', $product['subtitle'] ?? '', $product['images'][0] ?? null, true];
            }
        }
        return ['', '', '', null, false];
    }
}
