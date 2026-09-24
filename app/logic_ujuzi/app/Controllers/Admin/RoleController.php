<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Role;

class RoleController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.roles.index', [
            'pageTitle' => 'Roles',
            'activeNav' => 'roles',
            'roles' => Role::all(),
        ]);
    }

    public function edit(string $id): void
    {
        $role = Role::find((int) $id);
        if (!$role) {
            flashError('That role could not be found.');
            redirect('/admin/roles');
        }

        View::render('admin.roles.form', [
            'pageTitle' => 'Edit role',
            'activeNav' => 'roles',
            'role' => $role,
            'allRoles' => Role::all(),
            'errors' => [],
        ]);
    }

    public function update(string $id): void
    {
        $role = Role::find((int) $id);
        if (!$role) {
            flashError('That role could not be found.');
            redirect('/admin/roles');
        }

        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect('/admin/roles/' . $id . '/edit');
        }

        $name = trim((string) Request::post('name', ''));
        $errors = [];
        if ($name === '') {
            $errors[] = 'Role name is required.';
        }

        $managed = Request::post('managed_role_slugs', []);
        if (!is_array($managed)) {
            $managed = [];
        }
        $managed = array_values(array_filter($managed, fn($slug) => $slug !== $role['slug']));

        if ($errors) {
            $role['name'] = $name;
            $role['description'] = (string) Request::post('description', '');
            View::render('admin.roles.form', [
                'pageTitle' => 'Edit role',
                'activeNav' => 'roles',
                'role' => $role,
                'allRoles' => Role::all(),
                'errors' => $errors,
            ]);
            return;
        }

        Role::update((int) $id, [
            'name' => $name,
            'description' => trim((string) Request::post('description', '')),
            'has_admin_features' => (bool) Request::post('has_admin_features'),
            'is_under_organisation' => (bool) Request::post('is_under_organisation'),
            'can_manage_users' => (bool) Request::post('can_manage_users'),
            'managed_role_slugs' => $managed,
            'sort_order' => (int) Request::post('sort_order', $role['sort_order']),
        ]);

        flashSuccess('Role limits updated.');
        redirect('/admin/roles');
    }
}
