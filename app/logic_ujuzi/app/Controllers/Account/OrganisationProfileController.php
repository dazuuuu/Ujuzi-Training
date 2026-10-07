<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\Organisation;
use App\Models\OrganisationMembership;
use App\Models\User;

class OrganisationProfileController extends BaseAccountController
{
    public function edit(): void
    {
        $this->requireOrganisationOwner();

        $organisationId = (int) ($this->user['organisation_id'] ?? 0);
        $organisation = $organisationId > 0 ? Organisation::find($organisationId) : null;

        $this->render('account.organisation.form', [
            'pageTitle' => $organisation ? 'Your organisation' : 'Register your organisation',
            'activeNav' => 'organisation',
            'organisation' => $organisation,
            'isCourseOrganisation' => ($this->user['role_slug'] ?? '') === 'organisation_admin',
            'errors' => [],
            'form' => $organisation ?? ['name' => '', 'description' => '', 'visible_to_students' => 1],
        ]);
    }

    public function update(): void
    {
        $this->requireOrganisationOwner();

        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/organisation');
        }

        $name = trim((string) Request::post('name', ''));
        $description = trim((string) Request::post('description', ''));
        $visibleToStudents = Request::post('visible_to_students') === '1';
        $errors = [];
        if ($name === '') {
            $errors[] = 'Organisation name is required.';
        }

        $organisationId = (int) ($this->user['organisation_id'] ?? 0);
        $organisation = $organisationId > 0 ? Organisation::find($organisationId) : null;

        $contact = [];
        foreach (['phone', 'email', 'location', 'website'] as $key) {
            $contact[$key] = trim((string) Request::post($key, ''));
        }
        if ($contact['email'] !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($contact['website'] !== '' && !preg_match('#^https?://#i', $contact['website'])) {
            $contact['website'] = 'https://' . $contact['website'];
        }
        if ($contact['website'] !== '' && !filter_var($contact['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'The website must be a web address.';
        }
        $contact['logo_path'] = (string) ($organisation['logo_path'] ?? '');
        $upload = $_FILES['logo'] ?? null;
        if (!$errors && is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $contact['logo_path'] = \App\Services\UploadService::store($upload, 'organisations');
                \App\Services\UploadService::delete($organisation['logo_path'] ?? null);
            } catch (\App\Services\UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $isCourseOrganisation = ($this->user['role_slug'] ?? '') === 'organisation_admin';

        if ($errors) {
            $this->render('account.organisation.form', [
                'pageTitle' => $organisation ? 'Your organisation' : 'Register your organisation',
                'activeNav' => 'organisation',
                'organisation' => $organisation,
                'isCourseOrganisation' => $isCourseOrganisation,
                'errors' => $errors,
                'form' => ['name' => $name, 'description' => $description, 'visible_to_students' => $visibleToStudents ? 1 : 0] + $contact,
            ]);
            return;
        }

        $payload = [
            'name' => $name,
            'slug' => Organisation::uniqueSlug($name, $organisation ? $organisationId : null),
            'description' => $description,
            'is_active' => 1,
            'visible_to_students' => $isCourseOrganisation ? ($visibleToStudents ? 1 : 0) : ($organisation['visible_to_students'] ?? 1),
        ];

        if ($organisation) {
            Organisation::update($organisationId, $payload);
            Organisation::updateContact($organisationId, $contact);
            flashSuccess('Organisation updated.');
        } else {
            $newId = Organisation::create($payload);
            Organisation::updateContact($newId, $contact);
            User::assignOrganisation((int) $this->user['id'], $newId);
            try {
                OrganisationMembership::ensureApproved((int) $this->user['id'], $newId);
            } catch (\Throwable $e) {
                // Membership is a convenience for scoping; organisation_id is authoritative.
            }
            flashSuccess('Organisation registered. You can now add branches and branch admins.');
        }
        redirect('/account/organisation');
    }

    private function requireOrganisationOwner(): void
    {
        if (!in_array($this->user['role_slug'] ?? '', ['attachment_trainer', 'organisation_admin'], true)) {
            flashError('Only organisation admins and attachment providers manage an organisation here.');
            redirect('/account/dashboard');
        }
    }
}
