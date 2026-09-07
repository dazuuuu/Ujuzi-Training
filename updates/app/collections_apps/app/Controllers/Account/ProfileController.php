<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Authz;
use App\Core\Request;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\OrganisationBranch;
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
            $answers = is_array($form['response']['answers'] ?? null) ? $form['response']['answers'] : [];
            $orgId = (int) ($this->user['organisation_id'] ?? 0);
            if ($orgId > 0 && Authz::isOrganisationAdmin($this->user)) {
                foreach ($form['fields'] as $field) {
                    if (($field['field_type'] ?? '') !== 'branches') {
                        continue;
                    }
                    $current = $answers[$field['field_key']] ?? null;
                    if (!is_array($current) || $current === []) {
                        $answers[$field['field_key']] = OrganisationBranch::asFormRows($orgId);
                    }
                }
                if ($form['response']) {
                    $form['response']['answers'] = $answers;
                } elseif ($answers) {
                    $form['response'] = ['answers' => $answers, 'submitted_at' => null];
                }
            }
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
        if (!$form || empty($form['is_active']) || !in_array((int) $this->user['role_id'], $form['role_ids'], true) || ($form['purpose'] ?? 'profile') === 'course') {
            flashError('That form is not assigned to your profile.');
            redirect('/account/profile');
        }

        $fields = FormField::forForm($formId);
        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $existing = FormResponse::findForUserForm((int) $this->user['id'], $formId);
        $collected = FormAnswerService::collect($fields, $posted, $existing['answers'] ?? [], $this->user);

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
        $orgId = (int) ($this->user['organisation_id'] ?? 0);
        if ($orgId > 0 && Authz::isOrganisationAdmin($this->user)) {
            foreach ($fields as $field) {
                if (($field['field_type'] ?? '') !== 'branches') {
                    continue;
                }
                $rows = $collected['answers'][$field['field_key']] ?? [];
                if (is_array($rows)) {
                    OrganisationBranch::syncFromFormRows($orgId, $rows);
                }
                break;
            }
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
