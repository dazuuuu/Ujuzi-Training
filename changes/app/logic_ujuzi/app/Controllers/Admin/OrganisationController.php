<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\MailerException;
use App\Services\MailerService;

class OrganisationController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.organisations.index', [
            'pageTitle' => 'Organisations',
            'activeNav' => 'organisations',
            'organisations' => Organisation::all(),
            'invites' => OrganisationAdminInvite::latestByOrganisation(),
            'adminUsersByOrganisation' => Organisation::adminUsersByOrganisation(),
            'freshInvite' => $this->freshInviteFromSession(),
        ]);
    }

    public function create(): void
    {
        View::render('admin.organisations.form', [
            'pageTitle' => 'Add organisation',
            'activeNav' => 'organisations',
            'organisation' => null,
            'errors' => [],
            'form' => $this->blankForm(),
            'invite' => null,
            'freshInvite' => null,
            'adminUsers' => [],
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

        $fresh = $this->freshInviteFromSession();
        if ($fresh && (int) $fresh['organisation_id'] !== (int) $id) {
            $fresh = null;
        }

        // Pre-fill the admin-account panel from the organisation's existing
        // admin (if any), so editing shows their current details instead of
        // blank fields the super admin has to retype.
        $adminUsers = Organisation::adminUsers((int) $id);
        $primaryAdmin = $adminUsers[0] ?? null;
        $form = $organisation;
        if ($primaryAdmin) {
            $form['admin_first_name'] = $primaryAdmin['first_name'] ?? '';
            $form['admin_last_name'] = $primaryAdmin['last_name'] ?? '';
            $form['admin_other_names'] = $primaryAdmin['other_names'] ?? '';
            $form['admin_email'] = $primaryAdmin['email'] ?? '';
        }

        View::render('admin.organisations.form', [
            'pageTitle' => 'Edit organisation',
            'activeNav' => 'organisations',
            'organisation' => $organisation,
            'errors' => [],
            'form' => $form,
            'invite' => OrganisationAdminInvite::latestForOrganisation((int) $id),
            'freshInvite' => $fresh,
            'adminUsers' => $adminUsers,
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

    public function destroy(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/organisations');
        }
        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }
        Organisation::delete((int) $id);
        flashSuccess('"' . $organisation['name'] . '" was deleted, along with its courses, categories, branches, and memberships.');
        redirect('/admin/organisations');
    }

    public function share(): void
    {
        View::render('admin.organisations.share', [
            'pageTitle' => 'Share registration',
            'activeNav' => 'share',
            'organisations' => Organisation::all(),
            'invites' => OrganisationAdminInvite::latestByOrganisation(),
            'adminUsersByOrganisation' => Organisation::adminUsersByOrganisation(),
            'freshInvite' => $this->freshInviteFromSession(),
        ]);
    }

    public function generateInvite(string $id): void
    {
        $returnTo = $this->inviteReturnPath((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($returnTo);
        }

        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }
        if (empty($organisation['is_active'])) {
            flashError('Activate this organisation before generating a registration form link.');
            redirect($returnTo);
        }

        $invite = OrganisationAdminInvite::mint((int) $id, (int) $this->admin['id']);
        $_SESSION['fresh_org_admin_invite'] = $invite;

        $email = strtolower(trim((string) Request::post('invite_email', '')));
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flashError('The registration form link was generated, but that email address is not valid. Copy the URL below or try sending again.');
                redirect($returnTo);
            }
            try {
                MailerService::sendOrganisationAdminInvite(
                    $email,
                    $invite['url'],
                    $organisation['name'],
                    $invite['expires_at']
                );
                OrganisationAdminInvite::markEmailed((int) $invite['id'], $email);
                flashSuccess('Registration form emailed to ' . $email . ' via SMTP. The link expires in 5 minutes and accepts only one registration.');
            } catch (MailerException $e) {
                flashError($e->getMessage() . ' Open Super Admin → Settings and save your SMTP credentials.');
            }
            redirect($returnTo);
        }

        flashSuccess('Registration form link is ready. Copy it or email it to the client now. It expires in 5 minutes and accepts only one registration.');
        redirect($returnTo);
    }

    public function emailInvite(string $id): void
    {
        $returnTo = $this->inviteReturnPath((int) $id);
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($returnTo);
        }

        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }

        $fresh = $this->freshInviteFromSession();
        if (!$fresh || (int) $fresh['organisation_id'] !== (int) $id) {
            flashError('Generate a new registration form link first, then email it. Expired or already-used links cannot be resent.');
            redirect($returnTo);
        }

        $email = strtolower(trim((string) Request::post('invite_email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flashError('Enter a valid client email address to send the registration form.');
            redirect($returnTo);
        }

        try {
            MailerService::sendOrganisationAdminInvite(
                $email,
                $fresh['url'],
                $organisation['name'],
                $fresh['expires_at']
            );
            OrganisationAdminInvite::markEmailed((int) $fresh['id'], $email);
            flashSuccess('Registration form emailed to ' . $email . ' via SMTP. It expires in 5 minutes and works for one registration only.');
        } catch (MailerException $e) {
            flashError($e->getMessage() . ' Open Super Admin → Settings and save your SMTP credentials.');
        }
        redirect($returnTo);
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
        $visibleToStudents = Request::post('visible_to_students') === '1';
        $adminFirstName = trim((string) Request::post('admin_first_name', ''));
        $adminLastName = trim((string) Request::post('admin_last_name', ''));
        $adminOtherNames = trim((string) Request::post('admin_other_names', ''));
        $adminEmail = strtolower(trim((string) Request::post('admin_email', '')));
        $adminPassword = (string) Request::post('admin_password', '');
        $adminRoleSlug = (string) Request::post('admin_role_slug', 'organisation_admin');
        if (!in_array($adminRoleSlug, ['organisation_admin', 'attachment_trainer'], true)) {
            $adminRoleSlug = 'organisation_admin';
        }
        $existingAdmins = $id ? Organisation::adminUsers($id) : [];
        $primaryAdminId = (int) ($existingAdmins[0]['id'] ?? 0);
        $isEditingExistingAdmin = false;

        $errors = [];
        if ($name === '') {
            $errors[] = 'Organisation name is required.';
        }
        if ($adminEmail !== '') {
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid organisation admin email.';
            }
            $existingUser = User::findByIdentifier('email', $adminEmail);
            if ($existingUser && $primaryAdminId && (int) $existingUser['id'] === $primaryAdminId) {
                // Re-saving the same admin this org already has (e.g. the form was
                // just autofilled) — update their details rather than erroring.
                $isEditingExistingAdmin = true;
                if ($adminPassword !== '') {
                    $passwordError = User::passwordError($adminPassword);
                    if ($passwordError) {
                        $errors[] = $passwordError;
                    }
                }
            } elseif ($existingUser) {
                $errors[] = 'That organisation admin email is already used by another account.';
            } else {
                $passwordError = User::passwordError($adminPassword);
                if ($passwordError) {
                    $errors[] = $passwordError;
                }
            }
        } elseif ($adminPassword !== '' || $adminFirstName !== '' || $adminLastName !== '') {
            $errors[] = 'Enter the organisation admin email before saving admin account details.';
        }

        $form = [
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
            'visible_to_students' => $visibleToStudents ? 1 : 0,
            'admin_first_name' => $adminFirstName,
            'admin_last_name' => $adminLastName,
            'admin_other_names' => $adminOtherNames,
            'admin_email' => $adminEmail,
            'admin_role_slug' => $adminRoleSlug,
        ];
        if ($errors) {
            View::render('admin.organisations.form', [
                'pageTitle' => $id ? 'Edit organisation' : 'Add organisation',
                'activeNav' => 'organisations',
                'organisation' => $id ? Organisation::find($id) : null,
                'errors' => $errors,
                'form' => $form,
                'invite' => $id ? OrganisationAdminInvite::latestForOrganisation($id) : null,
                'freshInvite' => $id ? $this->freshInviteFromSession() : null,
                'adminUsers' => $id ? Organisation::adminUsers($id) : [],
            ]);
            return;
        }

        $payload = [
            'name' => $name,
            'slug' => Organisation::uniqueSlug($name, $id),
            'description' => $description,
            'is_active' => $isActive,
            'visible_to_students' => $visibleToStudents,
        ];

        if ($id) {
            Organisation::update($id, $payload);
            if ($isEditingExistingAdmin) {
                $this->updateOrganisationAdmin($primaryAdminId, $adminFirstName, $adminLastName, $adminOtherNames, $adminPassword);
                flashSuccess('Organisation updated.');
            } else {
                $adminCreated = $this->createOrganisationAdmin($id, $adminEmail, $adminPassword, $adminFirstName, $adminLastName, $adminOtherNames, $adminRoleSlug);
                flashSuccess($adminCreated ? 'Organisation updated and admin account created.' : 'Organisation updated.');
            }
        } else {
            $newId = Organisation::create($payload);
            $adminCreated = $this->createOrganisationAdmin($newId, $adminEmail, $adminPassword, $adminFirstName, $adminLastName, $adminOtherNames, $adminRoleSlug);
            flashSuccess($adminCreated ? 'Organisation created with an admin account.' : 'Organisation created.');
        }
        redirect('/admin/organisations');
    }

    private function updateOrganisationAdmin(int $userId, string $firstName, string $lastName, string $otherNames, string $password): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        User::update($userId, array_merge($user, [
            'first_name' => $firstName !== '' ? $firstName : $user['first_name'],
            'last_name' => $lastName !== '' ? $lastName : $user['last_name'],
            'other_names' => $otherNames,
        ]));
        if ($password !== '') {
            User::setPassword($userId, $password);
        }
    }

    private function createOrganisationAdmin(int $organisationId, string $email, string $password, string $firstName, string $lastName, string $otherNames = '', string $roleSlug = 'organisation_admin'): bool
    {
        if ($email === '') {
            return false;
        }
        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            return false;
        }
        $userId = User::create([
            'role_id' => (int) $role['id'],
            'organisation_id' => $organisationId,
            'email' => $email,
            'phone' => '',
            'first_name' => $firstName !== '' ? $firstName : 'Organisation',
            'last_name' => $lastName !== '' ? $lastName : ($roleSlug === 'attachment_trainer' ? 'Org Admin' : 'Admin'),
            'other_names' => $otherNames,
            'password' => $password,
            'is_active' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        try {
            OrganisationMembership::ensureApproved($userId, $organisationId);
        } catch (\Throwable $e) {
            // Memberships are a convenience for scoping; the user's organisation_id is authoritative here.
        }
        return true;
    }

    private function blankForm(): array
    {
        return [
            'name' => '',
            'description' => '',
            'is_active' => 1,
            'visible_to_students' => 1,
            'admin_first_name' => '',
            'admin_last_name' => '',
            'admin_other_names' => '',
            'admin_email' => '',
            'admin_role_slug' => 'organisation_admin',
        ];
    }

    private function inviteReturnPath(int $organisationId): string
    {
        $to = (string) Request::post('return_to', 'share');
        return match ($to) {
            'index' => '/admin/organisations#share',
            'edit' => '/admin/organisations/' . $organisationId . '/edit#invite',
            default => '/admin/share-registration#org-' . $organisationId,
        };
    }

    private function freshInviteFromSession(): ?array
    {
        $fresh = $_SESSION['fresh_org_admin_invite'] ?? null;
        if (!is_array($fresh) || empty($fresh['url']) || empty($fresh['expires_at'])) {
            return null;
        }
        if (strtotime((string) $fresh['expires_at']) <= time()) {
            unset($_SESSION['fresh_org_admin_invite']);
            return null;
        }
        return $fresh;
    }
}
