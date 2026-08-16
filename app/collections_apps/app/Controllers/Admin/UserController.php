<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;

class UserController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.users.index', [
            'pageTitle' => 'Users',
            'activeNav' => 'users',
            'users' => User::all(),
            'roles' => Role::all(),
            'organisations' => Organisation::all(),
        ]);
    }

    public function create(): void
    {
        if (Organisation::count() === 0) {
            flashError('Create an organisation first, then add users to it.');
            redirect('/admin/organisations/create');
        }

        View::render('admin.users.form', [
            'pageTitle' => 'Create user',
            'activeNav' => 'users',
            'user' => null,
            'roles' => Role::all(),
            'organisations' => Organisation::all(),
            'errors' => [],
            'form' => $this->blankForm(),
        ]);
    }

    public function store(): void
    {
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            flashError('That user could not be found.');
            redirect('/admin/users');
        }

        View::render('admin.users.form', [
            'pageTitle' => 'Edit user',
            'activeNav' => 'users',
            'user' => $user,
            'roles' => Role::all(),
            'organisations' => Organisation::all(),
            'errors' => [],
            'form' => $user,
        ]);
    }

    public function update(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            flashError('That user could not be found.');
            redirect('/admin/users');
        }
        $this->persist((int) $id);
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create');
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $phone = User::normalizePhone((string) Request::post('phone', ''));
        $firstName = trim((string) Request::post('first_name', ''));
        $lastName = trim((string) Request::post('last_name', ''));
        $roleId = (int) Request::post('role_id', 0);
        $organisationId = (int) Request::post('organisation_id', 0);
        $isActive = Request::post('is_active') === '1';

        $form = [
            'email' => $email,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'organisation_id' => $organisationId,
            'is_active' => $isActive ? 1 : 0,
        ];

        $errors = $this->validate($form, $id);
        if ($errors) {
            View::render('admin.users.form', [
                'pageTitle' => $id ? 'Edit user' : 'Create user',
                'activeNav' => 'users',
                'user' => $id ? User::find($id) : null,
                'roles' => Role::all(),
                'organisations' => Organisation::all(),
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $payload = $form;
        if ($id) {
            User::update($id, $payload);
            flashSuccess('User updated. Their dashboard and profile stay assigned to this role.');
        } else {
            User::create($payload);
            flashSuccess('User created. Dashboard and profile pages were provisioned, including any forms assigned to this role.');
        }
        redirect('/admin/users');
    }

    private function validate(array $form, ?int $ignoreId): array
    {
        $errors = [];
        if ($form['first_name'] === '') {
            $errors[] = 'First name is required.';
        }
        if ($form['email'] === '' && $form['phone'] === '') {
            $errors[] = 'Provide an email address or phone number so the user can sign in.';
        }
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        $role = Role::find((int) $form['role_id']);
        if (!$role) {
            $errors[] = 'Choose a role.';
        }

        if (!$form['organisation_id'] || !Organisation::find((int) $form['organisation_id'])) {
            $errors[] = 'Every LMS user belongs to an organisation.';
        }

        if ($form['email'] !== '') {
            $existing = User::findByIdentifier('email', $form['email']);
            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors[] = 'That email is already used by another user.';
            }
        }
        if ($form['phone'] !== '') {
            $existing = User::findByIdentifier('phone', $form['phone']);
            if ($existing && (int) $existing['id'] !== (int) $ignoreId) {
                $errors[] = 'That phone number is already used by another user.';
            }
        }

        return $errors;
    }

    private function blankForm(): array
    {
        return [
            'email' => '',
            'phone' => '',
            'first_name' => '',
            'last_name' => '',
            'role_id' => '',
            'organisation_id' => '',
            'is_active' => 1,
        ];
    }
}
