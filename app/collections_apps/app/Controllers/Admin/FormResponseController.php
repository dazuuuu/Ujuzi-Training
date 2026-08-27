<?php

namespace App\Controllers\Admin;

use App\Core\View;
use App\Models\Course;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;

class FormResponseController extends BaseAdminController
{
    public function index(): void
    {
        $counts = FormResponse::countsByForm();
        $forms = Form::all();
        foreach ($forms as &$form) {
            $stats = $counts[(int) $form['id']] ?? ['assigned' => 0, 'filled' => 0];
            $form['filled_count'] = $stats['filled'];
            $form['assigned_count'] = $stats['assigned'];
            if (($form['purpose'] ?? 'profile') === 'course') {
                $form['filled_count'] = count(Course::forForm((int) $form['id']));
            }
        }
        unset($form);

        View::render('admin.responses.index', [
            'pageTitle' => 'Form replies',
            'activeNav' => 'responses',
            'forms' => $forms,
        ]);
    }

    public function show(string $id): void
    {
        $form = Form::find((int) $id);
        if (!$form) {
            flashError('That form could not be found.');
            redirect('/admin/responses');
        }

        $fields = FormField::forForm((int) $form['id']);
        $submissions = [];
        if (($form['purpose'] ?? 'profile') === 'course') {
            foreach (Course::forForm((int) $form['id']) as $course) {
                $submissions[] = [
                    'user_name' => userDisplayName($course),
                    'email' => $course['email'] ?? '',
                    'phone' => $course['phone'] ?? '',
                    'role_name' => $course['role_name'] ?? 'Trainer',
                    'organisation_name' => $course['organisation_name'] ?? '',
                    'submitted_at' => $course['updated_at'] ?? $course['created_at'] ?? '',
                    'answers' => is_array($course['answers'] ?? null) ? $course['answers'] : [],
                    'meta' => $course['title'] ?? '',
                ];
            }
        } else {
            foreach (FormResponse::submittedForForm((int) $form['id']) as $row) {
                $submissions[] = [
                    'user_name' => userDisplayName($row),
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'role_name' => $row['role_name'] ?? '',
                    'organisation_name' => $row['organisation_name'] ?? '',
                    'submitted_at' => $row['submitted_at'] ?? '',
                    'answers' => $row['answers'] ?? [],
                    'meta' => '',
                ];
            }
        }

        View::render('admin.responses.show', [
            'pageTitle' => $form['title'] . ' replies',
            'activeNav' => 'responses',
            'formRecord' => $form,
            'fields' => $fields,
            'submissions' => $submissions,
        ]);
    }
}
