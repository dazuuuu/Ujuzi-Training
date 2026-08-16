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

    public function share(): void
    {
        View::render('admin.organisations.share', [
            'pageTitle' => 'Share registration',
            'activeNav' => 'share',
            'organisations' => Organisation::all(),
            'invites' => OrganisationAdminInvite::latestByOrganisation(),
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
                flashError('The registration form link was generated, but SMTP could not send the email. Copy the URL below and share it directly.');
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
            flashError('SMTP could not send the email. Copy the registration form URL and share it directly.');
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
