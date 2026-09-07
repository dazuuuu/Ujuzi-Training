<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Category;
use App\Services\UploadException;
use App\Services\UploadService;

class CategoryController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.categories.index', [
            'pageTitle' => 'Categories',
            'activeNav' => 'categories',
            'categories' => Category::allWithCounts(),
        ]);
    }

    public function create(): void
    {
        $this->showForm(null, [], ['category_key' => '', 'name' => '', 'tagline' => '', 'sort_order' => 0]);
    }

    public function edit(string $id): void
    {
        $category = Category::findRawById((int) $id);
        if (!$category) {
            redirect('/admin/categories');
        }
        $this->showForm($category, [], [
            'category_key' => $category['category_key'], 'name' => $category['name'],
            'tagline' => $category['tagline'], 'sort_order' => $category['sort_order'],
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        $category = Category::findRawById((int) $id);
        if (!$category) {
            redirect('/admin/categories');
        }
        $this->save($category);
    }

    public function deleteImage(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            $category = Category::findRawById((int) $id);
            if ($category) {
                UploadService::delete($category['image']);
                Category::clearImage((int) $id);
                flashSuccess('Cover image removed.');
            }
        }
        redirect('/admin/categories');
    }

    private function save(?array $category): void
    {
        $errors = [];
        if (!csrfVerify(Request::post('csrf_token'))) {
            $errors[] = 'Your session expired. Please resubmit the form.';
        }

        $name = trim((string) Request::post('name', ''));
        $tagline = trim((string) Request::post('tagline', ''));
        $sortOrder = (int) Request::post('sort_order', 0);
        $key = $category
            ? $category['category_key']
            : strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', Request::post('category_key') ?: $name), '-'));

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($key === '') {
            $errors[] = 'Category key is required.';
        }

        $imagePath = $category['image'] ?? null;
        $file = Request::file('image');
        if ($file && !empty($file['name'])) {
            try {
                $newImage = UploadService::store($file, 'categories');
                if ($imagePath) {
                    UploadService::delete($imagePath);
                }
                $imagePath = $newImage;
            } catch (UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            if ($category) {
                Category::update($category['id'], $name, $tagline, $imagePath, $sortOrder);
            } elseif (Category::keyExists($key)) {
                $errors[] = 'That category key already exists. Choose a different one.';
            } else {
                Category::create($key, $name, $tagline, $imagePath, $sortOrder);
            }
        }

        if ($errors) {
            $this->showForm($category, $errors, ['category_key' => $key, 'name' => $name, 'tagline' => $tagline, 'sort_order' => $sortOrder], $imagePath);
            return;
        }

        flashSuccess($category ? 'Category updated.' : 'Category created.');
        redirect('/admin/categories');
    }

    private function showForm(?array $category, array $errors, array $form, ?string $imageOverride = null): void
    {
        View::render('admin.categories.form', [
            'pageTitle' => $category ? 'Edit Category' : 'Add Category',
            'activeNav' => 'categories',
            'category' => $category ? array_merge($category, ['image' => $imageOverride ?? $category['image']]) : null,
            'errors' => $errors,
            'form' => $form,
        ]);
    }
}
