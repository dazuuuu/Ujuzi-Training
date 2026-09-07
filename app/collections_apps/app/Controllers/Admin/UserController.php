<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\Organisation;
use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;

class UserController extends BaseAdminController
{
    public function index(): void
    {
        $allUsers = User::all();
        $filters = [
            'q' => trim((string) Request::query('q', '')),
            'role_id' => (int) Request::query('role_id', 0),
            'organisation_id' => (int) Request::query('organisation_id', 0),
            'status' => trim((string) Request::query('status', '')),
        ];
        $users = array_values(array_filter($allUsers, function (array $user) use ($filters): bool {
            if ($filters['role_id'] > 0 && (int) ($user['role_id'] ?? 0) !== $filters['role_id']) {
                return false;
            }
            if ($filters['organisation_id'] > 0 && (int) ($user['organisation_id'] ?? 0) !== $filters['organisation_id']) {
                return false;
            }
            $status = (string) ($user['account_status'] ?? (!empty($user['is_active']) ? 'active' : 'blocked'));
            if ($filters['status'] !== '' && $status !== $filters['status']) {
                return false;
            }
            if ($filters['q'] !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($user['first_name'] ?? ''),
                    (string) ($user['last_name'] ?? ''),
                    (string) ($user['email'] ?? ''),
                    (string) ($user['phone'] ?? ''),
                    (string) ($user['organisation_name'] ?? ''),
                ]));
                if (strpos($haystack, strtolower($filters['q'])) === false) {
                    return false;
                }
            }
            return true;
        }));
        $groupedUsers = [];
        foreach ($users as $user) {
            $key = (string) ($user['role_slug'] ?? 'other');
            $groupedUsers[$key]['name'] = (string) ($user['role_name'] ?? 'Other');
            $groupedUsers[$key]['users'][] = $user;
        }
        View::render('admin.users.index', [
            'pageTitle' => 'Registered users',
            'activeNav' => 'registered-users',
            'users' => $users,
            'groupedUsers' => $groupedUsers,
            'filters' => $filters,
            'resultCount' => count($users),
            'roles' => Role::all(),
            'organisations' => Organisation::all(),
        ]);
    }

    public function show(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            flashError('That user could not be found.');
            redirect('/admin/users');
        }
        $forms = Form::forRole((int) $user['role_id'], false, 'profile');
        $withResponses = [];
        foreach ($forms as $form) {
            $form['fields'] = FormField::forForm((int) $form['id']);
            $form['response'] = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
            $withResponses[] = $form;
        }
        View::render('admin.users.show', [
            'pageTitle' => userDisplayName($user),
            'activeNav' => 'registered-users',
            'user' => $user,
            'forms' => $withResponses,
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

    public function status(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/registered-users');
        }
        $user = User::find((int) $id);
        $status = (string) Request::post('status', '');
        if (!$user || !in_array($status, ['active', 'blocked', 'suspended'], true)) {
            flashError('That user status change could not be applied.');
            redirect('/admin/registered-users');
        }
        User::setStatus((int) $id, $status);
        flashSuccess('User status changed to ' . $status . '.');
        redirect('/admin/registered-users');
    }

    public function destroy(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/registered-users');
        }
        $user = User::find((int) $id);
        if (!$user) {
            flashError('That user could not be found.');
            redirect('/admin/registered-users');
        }
        User::delete((int) $id);
        flashSuccess('User deleted permanently.');
        redirect('/admin/registered-users');
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
        $existingStatus = $id ? (string) (User::find($id)['account_status'] ?? '') : '';

        $form = [
            'email' => $email,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'organisation_id' => $organisationId,
            'is_active' => $isActive ? 1 : 0,
            'account_status' => $isActive ? 'active' : ($existingStatus === 'suspended' ? 'suspended' : 'blocked'),
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
            $this->markApprovedMember($id, (int) $payload['organisation_id']);
            flashSuccess('User updated. Their dashboard and profile stay assigned to this role.');
        } else {
            $newId = User::create($payload);
            $this->markApprovedMember($newId, (int) $payload['organisation_id']);
            flashSuccess('User created. Dashboard and profile pages were provisioned, including any forms assigned to this role.');
        }
        redirect('/admin/users');
    }

    private function markApprovedMember(int $userId, int $organisationId): void
    {
        try {
            OrganisationMembership::ensureApproved($userId, $organisationId);
        } catch (\Throwable $e) {
            // Memberships table is created by Super Admin → Updates.
        }
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
