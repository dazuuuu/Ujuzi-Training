<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\LoginRoles;
use App\Core\Request;
use App\Core\Url;
use App\Models\AttachmentApplication;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\Course;
use App\Models\Organisation;
use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\MailerException;
use App\Services\MailerService;

class PeopleController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireManager();
        $roleSlug = (string) ($this->user['role_slug'] ?? '');
        $users = Authz::scopedUsers($this->user);
        $attachmentApplications = [];
        if ($roleSlug === 'attachment_trainer') {
            $users = User::studentsForAttachmentProvider((int) $this->user['id']);
            try {
                [$from, $to] = \App\Services\AttachmentReport::dateRange();
                $attachmentApplications = array_values(array_filter(
                    AttachmentApplication::forProvider((int) $this->user['id']),
                    static function (array $a) use ($from, $to): bool {
                        $day = substr((string) ($a['selected_at'] ?? ''), 0, 10);
                        return ($from === '' || $day >= $from) && ($to === '' || $day <= $to);
                    }
                ));
                $fees = \App\Services\WalletService::feeSummaries(array_column($attachmentApplications, 'student_user_id'));
                foreach ($attachmentApplications as &$application) {
                    $application['fees'] = $fees[(int) $application['student_user_id']] ?? null;
                }
                unset($application);
            } catch (\Throwable $e) {
                $attachmentApplications = [];
            }
        } elseif ($roleSlug === 'trainer') {
            $users = Course::studentsForTrainer((int) $this->user['id']);
        }
        $this->render('account.people.index', [
            'pageTitle' => 'People',
            'from' => $from ?? '',
            'to' => $to ?? '',
            'activeNav' => 'people',
            'users' => $users,
            'manageableRoles' => Authz::manageableRoles($this->user),
            'directoryMode' => $roleSlug,
            'attachmentApplications' => $attachmentApplications,
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

    public function showImport(): void
    {
        $this->requireManager();
        $this->render('account.people.import', [
            'pageTitle' => 'Import people',
            'activeNav' => 'people',
            'roles' => Authz::manageableRoles($this->user),
            'errors' => [],
        ]);
    }

    public function import(): void
    {
        $this->requireManager();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people/import');
        }

        $roleId = (int) Request::post('role_id', 0);
        $role = Role::find($roleId);
        if (!$role || !Authz::canManageRole($this->user, $role['slug'])) {
            flashError('Choose a role you are allowed to manage.');
            redirect('/account/people/import');
        }

        $file = Request::file('csv_file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            flashError('Choose a CSV file with "name" and "email" columns.');
            redirect('/account/people/import');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            flashError('Could not read that file.');
            redirect('/account/people/import');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            flashError('That file looks empty.');
            redirect('/account/people/import');
        }
        $header = array_map(static fn($col): string => strtolower(trim((string) $col)), $header);
        $nameCol = array_search('name', $header, true);
        $emailCol = array_search('email', $header, true);
        if ($nameCol === false || $emailCol === false) {
            fclose($handle);
            flashError('The CSV needs a "name" column and an "email" column.');
            redirect('/account/people/import');
        }

        $created = 0;
        $skipped = 0;
        $organisationId = (int) $this->user['organisation_id'];
        while (($row = fgetcsv($handle)) !== false) {
            $name = trim((string) ($row[$nameCol] ?? ''));
            $email = strtolower(trim((string) ($row[$emailCol] ?? '')));
            if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            if (User::findByIdentifier('email', $email)) {
                $skipped++;
                continue;
            }
            [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');

            $newId = User::create([
                'email' => $email,
                'phone' => '',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role_id' => $roleId,
                'organisation_id' => $organisationId,
                'is_active' => 1,
                'password' => User::DEFAULT_PASSWORD,
                'must_change_password' => 1,
            ]);
            $this->markApprovedMember($newId, $organisationId);
            try {
                MailerService::sendAccountCreated(
                    $email,
                    Url::absolute(LoginRoles::loginPath($role['slug'])),
                    (string) ($role['name'] ?? 'account'),
                    User::DEFAULT_PASSWORD
                );
            } catch (MailerException $e) {
                // Row still counts as created — they can be given the default password manually.
            }
            $created++;
        }
        fclose($handle);

        flashSuccess("Imported {$created} " . ($created === 1 ? 'person' : 'people') . ($skipped ? ", skipped {$skipped} (missing/invalid/duplicate email)." : '.'));
        redirect('/account/people');
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

    public function resetPassword(string $id): void
    {
        $this->requireManager();
        $person = $this->managedPerson((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people/' . (int) $id);
        }

        $custom = trim((string) Request::post('password', ''));
        if ($custom !== '') {
            $error = User::passwordError($custom);
            if ($error) {
                flashError($error);
                redirect('/account/people/' . (int) $id);
            }
            User::setPassword((int) $person['id'], $custom);
            User::setMustChangePassword((int) $person['id'], true);
            flashSuccess('Password set. Share it with ' . userDisplayName($person) . ' — they will be asked to set their own on next login.');
            redirect('/account/people/' . (int) $id);
        }

        User::setPassword((int) $person['id'], User::DEFAULT_PASSWORD);
        User::setMustChangePassword((int) $person['id'], true);
        flashSuccess('Password reset to ' . User::DEFAULT_PASSWORD . '. They will be asked to set their own on next login.');
        redirect('/account/people/' . (int) $id);
    }

    public function status(string $id, string $status): void
    {
        $this->requireManager();
        $person = $this->managedPerson((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people/' . (int) $id);
        }
        if (!in_array($status, ['active', 'blocked', 'suspended'], true)) {
            flashError('That status change could not be applied.');
            redirect('/account/people/' . (int) $id);
        }

        User::setStatus((int) $person['id'], $status);
        flashSuccess('Account status changed to ' . $status . '.');
        redirect('/account/people/' . (int) $id);
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
        $otherNames = trim((string) Request::post('other_names', ''));
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
            'other_names' => $otherNames,
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
            $form['password'] = User::DEFAULT_PASSWORD;
            $form['must_change_password'] = 1;
            $newId = User::create($form);
            $this->markApprovedMember($newId, (int) $form['organisation_id']);

            $emailed = false;
            if ($email !== '') {
                try {
                    MailerService::sendAccountCreated(
                        $email,
                        Url::absolute(LoginRoles::loginPath($role['slug'])),
                        (string) ($role['name'] ?? 'account'),
                        User::DEFAULT_PASSWORD
                    );
                    $emailed = true;
                } catch (MailerException $e) {
                    $emailed = false;
                }
            }

            $message = $emailed
                ? 'Person created. Their login details were emailed to them.'
                : 'Person created. Give them their sign-in and this password: ' . User::DEFAULT_PASSWORD . ' — they will be asked to set their own on first login.';
            if ($email !== '') {
                $whatsappUrl = whatsappShareUrl(
                    "Your {$role['name']} login:\nEmail: {$email}\nPassword: " . User::DEFAULT_PASSWORD
                    . "\nSign in: " . Url::absolute(LoginRoles::loginPath($role['slug']))
                    . "\nYou'll be asked to set your own password on first login."
                );
                if ($whatsappUrl) {
                    $message .= ' <a href="' . e($whatsappUrl) . '" target="_blank" rel="noopener" class="underline font-bold">Share via WhatsApp</a>';
                }
            }
            flashSuccessHtml($message);
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
        $roleSlug = (string) ($this->user['role_slug'] ?? '');
        if (in_array($roleSlug, ['trainer', 'attachment_trainer'], true)) {
            return;
        }
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
            'other_names' => '',
            'role_id' => '',
            'is_active' => 1,
        ];
    }
}
