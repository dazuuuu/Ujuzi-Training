<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;

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

        $answers = [];
        $errors = [];
        foreach ($fields as $field) {
            $key = $field['field_key'];
            $value = isset($posted[$key]) ? trim((string) $posted[$key]) : '';
            if ($field['is_required'] && $value === '') {
                $errors[] = $field['label'] . ' is required.';
            }
            if ($field['field_type'] === 'number' && $value !== '' && !is_numeric($value)) {
                $errors[] = $field['label'] . ' must be a number.';
            }
            if ($field['field_type'] === 'dropdown' && $value !== '' && !in_array($value, $field['options'], true)) {
                $errors[] = $field['label'] . ' has an invalid option.';
            }
            $answers[$key] = $value;
        }

        if ($errors) {
            flashError(implode(' ', $errors));
            redirect('/account/profile');
        }

        FormResponse::save((int) $this->user['id'], $formId, $answers);
        flashSuccess('Your details were saved. You can come back and edit this form any time.');
        redirect('/account/profile');
    }
}
