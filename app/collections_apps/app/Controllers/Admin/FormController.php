<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Form;
use App\Models\FormField;
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

        $form = [
            'title' => $title,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
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
        $validFields = array_filter($form['fields'], fn(array $field): bool => trim($field['label']) !== '');
        if (!$validFields) {
            $errors[] = 'Add at least one field.';
        }
        foreach ($validFields as $field) {
            if ($field['field_type'] === 'dropdown' && empty($field['options'])) {
                $errors[] = 'Dropdown fields need at least one option.';
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
            'role_ids' => $form['role_ids'],
            'created_by_admin_id' => $this->admin['id'],
        ];

        if ($id) {
            Form::update($id, $payload);
            FormField::replaceForForm($id, $validFields);
            flashSuccess('Form saved. Assigned users can fill and re-edit it from their profile.');
        } else {
            $newId = Form::create($payload);
            FormField::replaceForForm($newId, $validFields);
            Form::syncRoles($newId, $form['role_ids']);
            flashSuccess('Form created and saved. It now appears on the profile pages of the assigned roles.');
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
            $options = $field['options'] ?? '';
            if (is_array($options)) {
                $options = implode("\n", $options);
            }
            $normalized[] = [
                'label' => trim((string) ($field['label'] ?? '')),
                'field_type' => (string) ($field['field_type'] ?? 'text'),
                'is_required' => !empty($field['is_required']),
                'options' => (string) $options,
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
            'role_ids' => [],
            'fields' => [[
                'label' => '',
                'field_type' => 'text',
                'is_required' => false,
                'options' => [],
            ]],
        ];
    }
}
