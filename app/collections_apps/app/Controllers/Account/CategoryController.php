<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\OrganisationCategory;

class CategoryController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireOrgAdmin();
        $this->render('account.categories.index', [
            'pageTitle' => 'Categories',
            'activeNav' => 'categories',
            'categories' => OrganisationCategory::forOrganisation((int) $this->user['organisation_id']),
        ]);
    }

    public function create(): void
    {
        $this->requireOrgAdmin();
        $this->render('account.categories.form', [
            'pageTitle' => 'Add category',
            'activeNav' => 'categories',
            'category' => null,
            'errors' => [],
            'form' => $this->blankForm(),
        ]);
    }

    public function store(): void
    {
        $this->requireOrgAdmin();
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $this->requireOrgAdmin();
        $category = $this->ownedCategory((int) $id);
        $this->render('account.categories.form', [
            'pageTitle' => 'Edit category',
            'activeNav' => 'categories',
            'category' => $category,
            'errors' => [],
            'form' => $category,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireOrgAdmin();
        $this->ownedCategory((int) $id);
        $this->persist((int) $id);
    }

    public function destroy(string $id): void
    {
        $this->requireOrgAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/categories');
        }
        $category = $this->ownedCategory((int) $id);
        try {
            OrganisationCategory::delete((int) $category['id']);
            flashSuccess('Category deleted.');
        } catch (\Throwable $e) {
            flashError('That category cannot be deleted while courses still use it.');
        }
        redirect('/account/categories');
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/account/categories/' . $id . '/edit' : '/account/categories/create');
        }

        $name = trim((string) Request::post('name', ''));
        $description = trim((string) Request::post('description', ''));
        $isActive = Request::post('is_active') === '1';
        $sortOrder = (int) Request::post('sort_order', 0);
        $orgId = (int) $this->user['organisation_id'];
        $errors = [];
        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        $form = [
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
            'sort_order' => $sortOrder,
        ];

        if ($errors) {
            $this->render('account.categories.form', [
                'pageTitle' => $id ? 'Edit category' : 'Add category',
                'activeNav' => 'categories',
                'category' => $id ? OrganisationCategory::find($id) : null,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $payload = [
            'organisation_id' => $orgId,
            'name' => $name,
            'slug' => OrganisationCategory::uniqueSlug($orgId, $name, $id),
            'description' => $description !== '' ? $description : null,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ];

        if ($id) {
            OrganisationCategory::update($id, $payload);
            flashSuccess('Category saved. Approved tutors for your organisation can pick it on course forms.');
        } else {
            OrganisationCategory::create($payload);
            flashSuccess('Category added. Approved tutors can now pick it when they create a course.');
        }
        redirect('/account/categories');
    }

    private function requireOrgAdmin(): void
    {
        if (!Authz::isOrganisationAdmin($this->user)) {
            flashError('Only organisation admins can manage categories.');
            redirect('/account/dashboard');
        }
    }

    private function ownedCategory(int $id): array
    {
        $category = OrganisationCategory::find($id);
        if (!$category || (int) $category['organisation_id'] !== (int) $this->user['organisation_id']) {
            flashError('That category could not be found.');
            redirect('/account/categories');
        }
        return $category;
    }

    private function blankForm(): array
    {
        return [
            'name' => '',
            'description' => '',
            'is_active' => 1,
            'sort_order' => 0,
        ];
    }
}
