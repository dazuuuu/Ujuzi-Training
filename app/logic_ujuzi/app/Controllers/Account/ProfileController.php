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
        $this->syncAccountFromAnswers();
        $forms = Form::forRole((int) $this->user['role_id'], true);
        $withFields = [];
        foreach ($forms as $form) {
            $form['fields'] = FormField::forForm((int) $form['id']);
            $form['response'] = FormResponse::findForUserForm((int) $this->user['id'], (int) $form['id']);
            $answers = is_array($form['response']['answers'] ?? null) ? $form['response']['answers'] : [];
            foreach ($form['fields'] as $field) {
                $fieldKey = (string) ($field['field_key'] ?? '');
                $fieldType = (string) ($field['field_type'] ?? '');
                if ($fieldType === 'name' && (!is_array($answers[$fieldKey] ?? null) || empty($answers[$fieldKey]))) {
                    $answers[$fieldKey] = [
                        'first' => (string) ($this->user['first_name'] ?? ''),
                        'last' => (string) ($this->user['last_name'] ?? ''),
                        'other' => (string) ($this->user['other_names'] ?? ''),
                    ];
                } elseif ($fieldType === 'name' && is_array($answers[$fieldKey] ?? null) && empty($answers[$fieldKey]['other'] ?? '')) {
                    $answers[$fieldKey]['other'] = (string) ($this->user['other_names'] ?? '');
                } elseif ($fieldType === 'phone' && empty($answers[$fieldKey])) {
                    $answers[$fieldKey] = (string) ($this->user['phone'] ?? '');
                } elseif ($fieldType === 'email' && empty($answers[$fieldKey])) {
                    $answers[$fieldKey] = (string) ($this->user['email'] ?? '');
                }
            }
            $orgId = (int) ($this->user['organisation_id'] ?? 0);
            foreach ($form['fields'] as $field) {
                if (($field['field_type'] ?? '') !== 'branches') {
                    continue;
                }
                $current = $answers[$field['field_key']] ?? null;
                if (!is_array($current) || $current === []) {
                    $answers[$field['field_key']] = Authz::isOrganisationAdmin($this->user)
                        ? OrganisationBranch::asFormRows($orgId)
                        : (($this->user['role_slug'] ?? '') === 'attachment_trainer'
                            ? OrganisationBranch::asProviderFormRows((int) $this->user['id'])
                            : OrganisationBranch::asUserFormRows((int) $this->user['id']));
                }
            }
            if ($form['response']) {
                $form['response']['answers'] = $answers;
            } elseif ($answers) {
                $form['response'] = ['answers' => $answers, 'submitted_at' => null];
            }
            $withFields[] = $form;
        }

        $this->render('account.profile', [
            'pageTitle' => 'My profile',
            'activeNav' => 'profile',
            'forms' => $withFields,
            'organisationMemberships' => OrganisationMembership::forUser((int) $this->user['id']),
        ]);
    }

    /** Anyone's profile picture (tutors set theirs with the rest of what students see). */
    /**
     * Accounts saved before other names and phone were copied from the
     * profile form get them now, so "Your details", certificates and phone
     * sign-in all see them.
     */
    private function syncAccountFromAnswers(): void
    {
        $changed = false;
        try {
            foreach (Form::forRole((int) $this->user['role_id'], true, 'profile') as $form) {
                $response = FormResponse::findForUserForm((int) $this->user['id'], (int) $form['id']);
                $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
                foreach (FormField::forForm((int) $form['id']) as $field) {
                    $value = $answers[$field['field_key']] ?? null;
                    if (($field['field_type'] ?? '') === 'name' && is_array($value)
                        && trim((string) ($value['other'] ?? '')) !== '' && trim((string) ($this->user['other_names'] ?? '')) === '') {
                        User::updateProfileNames((int) $this->user['id'], (string) ($this->user['first_name'] ?? ''), (string) ($this->user['last_name'] ?? ''), (string) $value['other']);
                        $changed = true;
                    }
                    if (($field['field_type'] ?? '') === 'phone' && is_scalar($value) && trim((string) $value) !== '' && trim((string) ($this->user['phone'] ?? '')) === '') {
                        User::syncPhone((int) $this->user['id'], (string) $value);
                        $changed = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            return;
        }
        if ($changed) {
            $this->user = User::find((int) $this->user['id']) ?: $this->user;
        }
    }

    public function updatePhoto(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/profile');
        }
        $upload = $_FILES['photo'] ?? null;
        if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a picture first.');
            redirect('/account/profile');
        }
        try {
            $path = \App\Services\UploadService::store($upload, 'profiles');
        } catch (\App\Services\UploadException $e) {
            flashError($e->getMessage());
            redirect('/account/profile');
        }
        \App\Services\UploadService::delete($this->user['photo_path'] ?? null);
        User::setPhoto((int) $this->user['id'], $path);
        flashSuccess('Profile picture saved.');
        redirect('/account/profile');
    }

    /** A tutor's picture, contacts and background, shown to the students of their courses. */
    public function updatePublic(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/profile#public-profile');
        }
        if (($this->user['role_slug'] ?? '') !== 'trainer') {
            redirect('/account/profile');
        }

        $fields = [];
        foreach (User::PUBLIC_PROFILE_FIELDS as $key) {
            $fields[$key] = trim((string) Request::post($key, ''));
        }
        $fields['photo_path'] = (string) ($this->user['photo_path'] ?? '');
        $phone = User::normalizePhone((string) Request::post('phone', ''));

        $errors = [];
        foreach (['linkedin_url' => 'LinkedIn', 'social_url' => 'Social media'] as $key => $label) {
            if ($fields[$key] !== '' && !preg_match('#^https?://#i', $fields[$key])) {
                $fields[$key] = 'https://' . $fields[$key];
            }
            if ($fields[$key] !== '' && !filter_var($fields[$key], FILTER_VALIDATE_URL)) {
                $errors[] = $label . ' must be a web link.';
            }
        }
        if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 9) {
            $errors[] = 'Enter a phone number students can call.';
        }
        $upload = $_FILES['photo'] ?? null;
        if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $fields['photo_path'] = \App\Services\UploadService::store($upload, 'tutors');
                \App\Services\UploadService::delete($this->user['photo_path'] ?? null);
            } catch (\App\Services\UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($errors) {
            flashError(implode(' ', $errors));
            redirect('/account/profile#public-profile');
        }

        User::updatePublicProfile((int) $this->user['id'], $fields, $phone);
        $missing = User::missingPublicProfile(User::find((int) $this->user['id']) ?? []);
        $missing
            ? flashError('Saved. Still needed before you can create courses: ' . implode(', ', $missing) . '.')
            : flashSuccess('Saved. Students of your courses can now see your profile.');
        redirect('/account/profile#public-profile');
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect('/account/profile');
        }

        if (Request::post('account_update') === '1') {
            // Once an account has a sign-in email, it's locked — only accounts
            // that started with no email (created without one) can add one here.
            $email = !empty($this->user['email'])
                ? (string) $this->user['email']
                : strtolower(trim((string) Request::post('email', '')));
            $phone = (string) ($this->user['phone'] ?? '');
            $errors = [];
            if ($email === '' && $phone === '') {
                $errors[] = 'Keep at least an email address or phone number for sign-in.';
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            }
            if ($email !== '') {
                $existingEmail = User::findByIdentifier('email', $email);
                if ($existingEmail && (int) $existingEmail['id'] !== (int) $this->user['id']) {
                    $errors[] = 'That email is already used by another account.';
                }
            }
            if ($errors) {
                flashError(implode(' ', $errors));
                redirect('/account/profile');
            }
            if ($email !== '' && empty($this->user['email'])) {
                // A new sign-in email counts once its owner verifies it.
                $error = AccountLockController::sendVerification((int) $this->user['id'], $email, null, userDisplayName($this->user));
                $error === null
                    ? flashSuccess('We sent a link to ' . $email . '. Press "Verify my email" in it to add this email to your account.')
                    : flashError($error);
                redirect('/account/profile');
            }
            User::updateCredentials((int) $this->user['id'], $email, $phone);
            flashSuccess('Your sign-in details were updated.');
            redirect('/account/profile');
        }

        $formId = (int) Request::post('form_id', 0);
        $form = Form::find($formId);
        if (!$form || empty($form['is_active']) || !in_array((int) $this->user['role_id'], $form['role_ids'], true) || ($form['purpose'] ?? 'profile') === 'course') {
            flashError('That form is not assigned to your profile.');
            redirect('/account/profile');
        }

        $fields = FormField::forForm($formId);
        $saveField = trim((string) Request::post('save_field', ''));
        $fieldKeys = array_map(static fn(array $field): string => (string) ($field['field_key'] ?? ''), $fields);
        if ($saveField !== '' && !in_array($saveField, $fieldKeys, true)) {
            $saveField = '';
        }
        $fieldsToValidate = $saveField !== ''
            ? array_values(array_filter($fields, static fn(array $field): bool => (string) ($field['field_key'] ?? '') === $saveField))
            : $fields;
        $posted = Request::post('answers', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        $existing = FormResponse::findForUserForm((int) $this->user['id'], $formId);
        $collected = FormAnswerService::collect($fieldsToValidate, $posted, $existing['answers'] ?? [], $this->user);

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
        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') !== 'branches') {
                continue;
            }
            $rows = $collected['answers'][$field['field_key']] ?? [];
            if (is_array($rows)) {
                if (Authz::isOrganisationAdmin($this->user) && $orgId > 0) {
                    OrganisationBranch::syncFromFormRows($orgId, $rows);
                } elseif (($this->user['role_slug'] ?? '') === 'attachment_trainer') {
                    OrganisationBranch::syncProviderFormRows((int) $this->user['id'], $rows);
                } else {
                    OrganisationBranch::syncUserFormRows((int) $this->user['id'], $rows);
                }
            }
            break;
        }
        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') === 'phone' && is_scalar($collected['answers'][$field['field_key']] ?? null)) {
                User::syncPhone((int) $this->user['id'], (string) $collected['answers'][$field['field_key']]);
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
                    User::updateProfileNames((int) $this->user['id'], $first, $last, (string) ($raw['other'] ?? ''));
                }
            }
            break;
        }
        $freshUser = User::find((int) $this->user['id']) ?: $this->user;
        if ($saveField === '' && !AccountRedirect::needsProfile($freshUser)) {
            flashSuccess('Your details were saved. Welcome to your dashboard.');
            redirect('/account/dashboard');
        }
        $hasOrganisationField = false;
        foreach ($fieldsToValidate as $field) {
            if (($field['field_type'] ?? '') === 'organisation') {
                $hasOrganisationField = true;
                break;
            }
        }
        if ($hasOrganisationField && OrganisationMembership::isTrainerRole((string) ($this->user['role_slug'] ?? ''))) {
            flashSuccess('Your organisation selection was saved and is waiting for approval.');
            redirect('/account/profile');
        }
        flashSuccess('Your details were saved. You can come back and edit this form any time.');
        redirect('/account/profile');
    }
}
