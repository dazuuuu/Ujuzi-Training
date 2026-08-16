<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Organisation;
use App\Models\OrganisationAdminInvite;
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
            'form' => ['name' => '', 'description' => '', 'is_active' => 1],
            'invite' => null,
            'freshInvite' => null,
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

        View::render('admin.organisations.form', [
            'pageTitle' => 'Edit organisation',
            'activeNav' => 'organisations',
            'organisation' => $organisation,
            'errors' => [],
            'form' => $organisation,
            'invite' => OrganisationAdminInvite::latestForOrganisation((int) $id),
            'freshInvite' => $fresh,
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

    public function generateInvite(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/organisations/' . (int) $id . '/edit');
        }

        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }
        if (empty($organisation['is_active'])) {
            flashError('Activate this organisation before generating a registration link.');
            redirect('/admin/organisations/' . (int) $id . '/edit');
        }

        $invite = OrganisationAdminInvite::mint((int) $id, (int) $this->admin['id']);
        $_SESSION['fresh_org_admin_invite'] = $invite;
        flashSuccess('A 5-minute organisation-admin registration link is ready. Copy or email it now — it cannot be shown again after you leave this page, and it only accepts one registration.');
        redirect('/admin/organisations/' . (int) $id . '/edit#invite');
    }

    public function emailInvite(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/organisations/' . (int) $id . '/edit');
        }

        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation could not be found.');
            redirect('/admin/organisations');
        }

        $fresh = $this->freshInviteFromSession();
        if (!$fresh || (int) $fresh['organisation_id'] !== (int) $id) {
            flashError('Generate a new registration link first, then email it. Expired or already-used links cannot be resent.');
            redirect('/admin/organisations/' . (int) $id . '/edit#invite');
        }

        $email = strtolower(trim((string) Request::post('invite_email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flashError('Enter a valid email address to send the registration link.');
            redirect('/admin/organisations/' . (int) $id . '/edit#invite');
        }

        try {
            MailerService::sendOrganisationAdminInvite(
                $email,
                $fresh['url'],
                $organisation['name'],
                $fresh['expires_at']
            );
            OrganisationAdminInvite::markEmailed((int) $fresh['id'], $email);
            flashSuccess('Registration link emailed to ' . $email . '. It expires in 5 minutes and works for one registration only.');
        } catch (MailerException $e) {
            flashError('The link was generated but the email could not be sent. Copy the URL and share it directly.');
        }
        redirect('/admin/organisations/' . (int) $id . '/edit#invite');
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
                'invite' => $id ? OrganisationAdminInvite::latestForOrganisation($id) : null,
                'freshInvite' => $id ? $this->freshInviteFromSession() : null,
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
