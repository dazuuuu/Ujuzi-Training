<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Request;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\OrganisationMembership;
use App\Models\User;
use App\Services\FormAnswerService;

class ProfileController extends BaseAccountController
{
    public function index(): void
    {
        $forms = Form::forRole((int) $this->user['role_id'], true);
        $withFields = [];
        foreach ($forms as $form) {
            $form['fields'] = FormField::forForm((int) $form['id']);
            $form['response'] = FormResponse::findForUserForm((int) $this->user['id'], (int) $form['id']);
            $withFields[] = $form;
        }

        $this->render('account.profile', [
            'pageTitle' => 'My profile',
            'activeNav' => 'profile',
            'forms' => $withFields,
        ]);
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect('/account/profile');
        }

        $formId = (int) Request::post('form_id', 0);
        $form = Form::find($formId);
        if (!$form || empty($form['is_active']) || !in_array((int) $this->user['role_id'], $form['role_ids'], true)) {
            flashError('That form is not assigned to your role.');
            redirect('/account/profile');
        }

        $fields = FormField::forForm($formId);
        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $existing = FormResponse::findForUserForm((int) $this->user['id'], $formId);
        $collected = FormAnswerService::collect($fields, $posted, $existing['answers'] ?? []);

        if ($collected['errors']) {
            flashError(implode(' ', $collected['errors']));
            redirect('/account/profile');
        }

        FormResponse::save((int) $this->user['id'], $formId, $collected['answers']);
        try {
            OrganisationMembership::syncFromProfileAnswers(
                (int) $this->user['id'],
                (string) ($this->user['role_slug'] ?? ''),
                $fields,
                $collected['answers']
            );
        } catch (\Throwable $e) {
            // Memberships table is created by Super Admin → Updates.
        }
        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') !== 'name') {
                continue;
            }
            $raw = $collected['answers'][$field['field_key']] ?? [];
            if (is_array($raw)) {
                $first = trim((string) ($raw['first'] ?? ''));
                $last = trim((string) ($raw['last'] ?? ''));
                if ($first !== '' || $last !== '') {
                    User::updateProfileNames((int) $this->user['id'], $first, $last);
                }
            }
            break;
        }
        $fresh = array_merge($this->user);
        if (!AccountRedirect::needsProfile($fresh)) {
            flashSuccess('Your details were saved. Welcome to your dashboard.');
            redirect('/account/dashboard');
        }
        flashSuccess('Your details were saved. You can come back and edit this form any time.');
        redirect('/account/profile');
    }
}
