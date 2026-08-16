<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Organisation;

class OrganisationController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.organisations.index', [
            'pageTitle' => 'Organisations',
            'activeNav' => 'organisations',
            'organisations' => Organisation::all(),
        ]);
    }

    public function create(): void
    {
        View::render('admin.organisations.form', [
            'pageTitle' => 'Add organisation',
            'activeNav' => 'organisations',
            'organisation' => null,
            'errors' => [],
            'form' => ['name' => '', 'description' => '', 'is_active' => 1],
        ]);
    }

    public function store(): void
    {
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }

        View::render('admin.organisations.form', [
            'pageTitle' => 'Edit organisation',
            'activeNav' => 'organisations',
            'organisation' => $organisation,
            'errors' => [],
            'form' => $organisation,
        ]);
    }

    public function update(string $id): void
    {
        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }
        $this->persist((int) $id);
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/admin/organisations/' . $id . '/edit' : '/admin/organisations/create');
        }

        $name = trim((string) Request::post('name', ''));
        $description = trim((string) Request::post('description', ''));
        $isActive = Request::post('is_active') === '1';
        $errors = [];
        if ($name === '') {
            $errors[] = 'Organisation name is required.';
        }

        $form = ['name' => $name, 'description' => $description, 'is_active' => $isActive ? 1 : 0];
        if ($errors) {
            View::render('admin.organisations.form', [
                'pageTitle' => $id ? 'Edit organisation' : 'Add organisation',
                'activeNav' => 'organisations',
                'organisation' => $id ? Organisation::find($id) : null,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $payload = [
            'name' => $name,
            'slug' => Organisation::uniqueSlug($name, $id),
            'description' => $description,
            'is_active' => $isActive,
        ];

        if ($id) {
            Organisation::update($id, $payload);
            flashSuccess('Organisation updated.');
        } else {
            Organisation::create($payload);
            flashSuccess('Organisation created.');
        }
        redirect('/admin/organisations');
    }
}
