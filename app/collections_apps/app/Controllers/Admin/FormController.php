<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldTypes;
use App\Models\Role;

class FormController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.forms.index', [
            'pageTitle' => 'Forms',
            'activeNav' => 'forms',
            'forms' => Form::all(),
        ]);
    }

    public function create(): void
    {
        View::render('admin.forms.form', [
            'pageTitle' => 'Create form',
            'activeNav' => 'forms',
            'formRecord' => null,
            'roles' => Role::all(),
            'errors' => [],
            'form' => $this->blankForm(),
        ]);
    }

    public function store(): void
    {
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $formRecord = Form::find((int) $id);
        if (!$formRecord) {
            flashError('That form could not be found.');
            redirect('/admin/forms');
        }

        View::render('admin.forms.form', [
            'pageTitle' => 'Edit form',
            'activeNav' => 'forms',
            'formRecord' => $formRecord,
            'roles' => Role::all(),
            'errors' => [],
            'form' => [
                'title' => $formRecord['title'],
                'description' => $formRecord['description'],
                'is_active' => $formRecord['is_active'],
                'purpose' => $formRecord['purpose'] ?? 'profile',
                'role_ids' => $formRecord['role_ids'],
                'fields' => FormField::forForm((int) $formRecord['id']),
            ],
        ]);
    }

    public function update(string $id): void
    {
        $formRecord = Form::find((int) $id);
        if (!$formRecord) {
            flashError('That form could not be found.');
            redirect('/admin/forms');
        }
        $this->persist((int) $id);
    }

    public function destroy(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/forms');
        }
        $formRecord = Form::find((int) $id);
        if (!$formRecord) {
            flashError('That form could not be found.');
            redirect('/admin/forms');
        }
        Form::delete((int) $id);
        flashSuccess('Form deleted.');
        redirect('/admin/forms');
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/admin/forms/' . $id . '/edit' : '/admin/forms/create');
        }

        $title = trim((string) Request::post('title', ''));
        $description = trim((string) Request::post('description', ''));
        $roleIds = Request::post('role_ids', []);
        if (!is_array($roleIds)) {
            $roleIds = [];
        }
        $fields = Request::post('fields', []);
        if (!is_array($fields)) {
            $fields = [];
        }
        $isActive = Request::post('is_active') === '1';
        $purpose = Request::post('purpose') === 'course' ? 'course' : 'profile';

        $form = [
            'title' => $title,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
            'purpose' => $purpose,
            'role_ids' => array_map('intval', $roleIds),
            'fields' => $this->normalizePostedFields($fields),
        ];

        $errors = [];
        if ($title === '') {
            $errors[] = 'Form title is required.';
        }
        if (!$form['role_ids']) {
            $errors[] = 'Assign this form to at least one role (students, organisation admins, trainers, or attachment trainers).';
        }
        $validFields = array_values(array_filter($form['fields'], function (array $field): bool {
            if (FormFieldTypes::isLayout($field['field_type'])) {
                return true;
            }
            return trim($field['label']) !== '';
        }));
        $inputCount = 0;
        foreach ($validFields as $field) {
            if (!FormFieldTypes::isLayout($field['field_type'])) {
                $inputCount++;
            }
        }
        if (!$validFields) {
            $errors[] = 'Add at least one field.';
        } elseif ($inputCount === 0) {
            $errors[] = 'Add at least one input field (headings and instructions do not count).';
        }
        foreach ($validFields as $field) {
            if (FormFieldTypes::needsChoices($field['field_type']) && empty($field['choices']) && empty($field['options'])) {
                $errors[] = FormFieldTypes::label($field['field_type']) . ' fields need at least one choice value.';
            }
            $minSelect = (int) ($field['min_select'] ?? 0);
            $maxSelect = (int) ($field['max_select'] ?? 0);
            if ($maxSelect > 0 && $minSelect > $maxSelect) {
                $errors[] = FormFieldTypes::label($field['field_type']) . ' minimum selections cannot be greater than the maximum.';
            }
        }

        if ($errors) {
            View::render('admin.forms.form', [
                'pageTitle' => $id ? 'Edit form' : 'Create form',
                'activeNav' => 'forms',
                'formRecord' => $id ? Form::find($id) : null,
                'roles' => Role::all(),
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $payload = [
            'title' => $title,
            'description' => $description,
            'is_active' => $isActive,
            'purpose' => $purpose,
            'role_ids' => $form['role_ids'],
            'created_by_admin_id' => $this->admin['id'],
        ];

        if ($id) {
            Form::update($id, $payload);
            FormField::replaceForForm($id, $validFields);
            flashSuccess($purpose === 'course'
                ? 'Course form saved. Approved trainers can use it to create courses.'
                : 'Form saved. Assigned users can fill and re-edit it from their profile.');
        } else {
            $newId = Form::create($payload);
            FormField::replaceForForm($newId, $validFields);
            Form::syncRoles($newId, $form['role_ids']);
            flashSuccess($purpose === 'course'
                ? 'Course form created. Assign it to trainers so they can create courses after an organisation approves them.'
                : 'Form created and saved. It now appears on the profile pages of the assigned roles.');
        }
        redirect('/admin/forms');
    }

    private function normalizePostedFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $choices = $field['choices'] ?? $field['options'] ?? [];
            if (is_string($choices)) {
                $choices = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $choices) ?: [])));
            } elseif (!is_array($choices)) {
                $choices = [];
            } else {
                $choices = array_values(array_filter(array_map('trim', $choices), fn($item) => $item !== ''));
            }
            $normalized[] = [
                'label' => trim((string) ($field['label'] ?? '')),
                'field_key' => trim((string) ($field['field_key'] ?? '')),
                'field_type' => (string) ($field['field_type'] ?? 'text'),
                'is_required' => !empty($field['is_required']),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'help_text' => trim((string) ($field['help_text'] ?? '')),
                'choices' => $choices,
                'options' => $choices,
                'range_min' => trim((string) ($field['range_min'] ?? '')),
                'range_max' => trim((string) ($field['range_max'] ?? '')),
                'allow_other' => !empty($field['allow_other']),
                'columns' => max(1, min(3, (int) ($field['columns'] ?? 1))),
                'min_select' => max(0, (int) ($field['min_select'] ?? 0)),
                'max_select' => max(0, (int) ($field['max_select'] ?? 0)),
                'select_all' => !empty($field['select_all']),
                'org_mode' => (($field['field_type'] ?? '') === 'organisation' && ($field['org_mode'] ?? '') === 'multiple')
                    ? 'multiple'
                    : 'single',
            ];
        }
        return $normalized;
    }

    private function blankForm(): array
    {
        return [
            'title' => '',
            'description' => '',
            'is_active' => 1,
            'purpose' => 'profile',
            'role_ids' => [],
            'fields' => [[
                'label' => '',
                'field_key' => '',
                'field_type' => 'text',
                'is_required' => false,
                'placeholder' => '',
                'help_text' => '',
                'options' => [],
                'choices' => ['', ''],
                'allow_other' => false,
                'columns' => 1,
                'min_select' => 0,
                'max_select' => 0,
                'select_all' => false,
                'org_mode' => 'single',
            ]],
        ];
    }
}
