<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\Course;
use App\Models\Organisation;
use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;

class PeopleController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireManager();
        $roleSlug = (string) ($this->user['role_slug'] ?? '');
        $users = Authz::scopedUsers($this->user);
        if ($roleSlug === 'attachment_trainer') {
            $users = User::studentsForAttachmentProvider((int) $this->user['id']);
        } elseif ($roleSlug === 'trainer') {
            $users = Course::studentsForTrainer((int) $this->user['id']);
        }
        $this->render('account.people.index', [
            'pageTitle' => 'People',
            'activeNav' => 'people',
            'users' => $users,
            'manageableRoles' => Authz::manageableRoles($this->user),
            'directoryMode' => $roleSlug,
        ]);
    }

    public function create(): void
    {
        $this->requireManager();
        $this->render('account.people.form', [
            'pageTitle' => 'Add person',
            'activeNav' => 'people',
            'person' => null,
            'roles' => Authz::manageableRoles($this->user),
            'errors' => [],
            'form' => $this->blankForm(),
        ]);
    }

    public function store(): void
    {
        $this->requireManager();
        $this->persist(null);
    }

    public function show(string $id): void
    {
        $this->requireManager();
        $person = $this->managedPerson((int) $id);
        $forms = Form::forRole((int) $person['role_id'], false);
        $withResponses = [];
        foreach ($forms as $form) {
            $form['fields'] = FormField::forForm((int) $form['id']);
            $form['response'] = FormResponse::findForUserForm((int) $person['id'], (int) $form['id']);
            $withResponses[] = $form;
        }

        $this->render('account.people.show', [
            'pageTitle' => userDisplayName($person),
            'activeNav' => 'people',
            'person' => $person,
            'forms' => $withResponses,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireManager();
        $person = $this->managedPerson((int) $id);
        $this->render('account.people.form', [
            'pageTitle' => 'Edit person',
            'activeNav' => 'people',
            'person' => $person,
            'roles' => Authz::manageableRoles($this->user),
            'errors' => [],
            'form' => $person,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireManager();
        $this->managedPerson((int) $id);
        $this->persist((int) $id);
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/account/people/' . $id . '/edit' : '/account/people/create');
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $phone = User::normalizePhone((string) Request::post('phone', ''));
        $firstName = trim((string) Request::post('first_name', ''));
        $lastName = trim((string) Request::post('last_name', ''));
        $roleId = (int) Request::post('role_id', 0);
        $isActive = Request::post('is_active') === '1';
        $role = Role::find($roleId);
        $errors = [];

        if ($firstName === '') {
            $errors[] = 'First name is required.';
        }
        if ($email === '' && $phone === '') {
            $errors[] = 'Provide an email address or phone number so they can sign in.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!$role || !Authz::canManageRole($this->user, $role['slug'])) {
            $errors[] = 'Choose a role you are allowed to manage.';
        }

        $form = [
            'email' => $email,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role_id' => $roleId,
            'organisation_id' => (int) $this->user['organisation_id'],
            'is_active' => $isActive ? 1 : 0,
        ];

        if ($email !== '') {
            $existing = User::findByIdentifier('email', $email);
            if ($existing && (int) $existing['id'] !== (int) $id) {
                $errors[] = 'That email is already used by another user.';
            }
        }
        if ($phone !== '') {
            $existing = User::findByIdentifier('phone', $phone);
            if ($existing && (int) $existing['id'] !== (int) $id) {
                $errors[] = 'That phone number is already used by another user.';
            }
        }

        if ($errors) {
            $this->render('account.people.form', [
                'pageTitle' => $id ? 'Edit person' : 'Add person',
                'activeNav' => 'people',
                'person' => $id ? User::find($id) : null,
                'roles' => Authz::manageableRoles($this->user),
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        if ($id) {
            User::update($id, $form);
            $this->markApprovedMember($id, (int) $form['organisation_id']);
            flashSuccess('Person updated. Their dashboard and profile stay in place.');
        } else {
            $newId = User::create($form);
            $this->markApprovedMember($newId, (int) $form['organisation_id']);
            flashSuccess('Person created. Dashboard and profile pages were provisioned for their role.');
        }
        redirect('/account/people');
    }

    private function markApprovedMember(int $userId, int $organisationId): void
    {
        try {
            OrganisationMembership::ensureApproved($userId, $organisationId, (int) $this->user['id']);
        } catch (\Throwable $e) {
            // Memberships table is created by Super Admin → Updates.
        }
    }

    private function requireManager(): void
    {
        if (!Authz::canManageUsers($this->user) || empty($this->user['organisation_id'])) {
            flashError('Your role does not include admin tools.');
            redirect('/account/dashboard');
        }
        if (!Organisation::find((int) $this->user['organisation_id'])) {
            flashError('Your organisation could not be found.');
            redirect('/account/dashboard');
        }
    }

    private function managedPerson(int $id): array
    {
        $person = User::find($id);
        if (!$person || !Authz::canAccessUser($this->user, $person) || (int) $person['id'] === (int) $this->user['id']) {
            flashError('You cannot manage that person.');
            redirect('/account/people');
        }
        return $person;
    }

    private function blankForm(): array
    {
        return [
            'email' => '',
            'phone' => '',
            'first_name' => '',
            'last_name' => '',
            'role_id' => '',
            'is_active' => 1,
        ];
    }
}
