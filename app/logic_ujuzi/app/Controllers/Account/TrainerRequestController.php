<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;
use App\Models\User;

class TrainerRequestController extends BaseAccountController
{
    public function index(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can approve trainers.');
            redirect('/account/dashboard');
        }

        $orgId = (int) $this->user['organisation_id'];
        $this->render('account.trainer-requests.index', [
            'pageTitle' => 'Trainer Requests',
            'activeNav' => 'trainer_requests',
            'pendingTrainerRequests' => OrganisationMembership::pendingTrainersForOrganisation($orgId),
            'pendingStudentRequests' => OrganisationMembership::pendingStudentsForOrganisation($orgId),
            'categories' => OrganisationCategory::forOrganisation($orgId, true),
            'approvedStudents' => OrganisationMembership::approvedStudents($orgId),
        ]);
    }

    /** Full view of one student's request: their details, requested categories (editable) and Approve/Reject. */
    public function reviewStudent(string $id): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can review students.');
            redirect('/account/dashboard');
        }
        $orgId = (int) $this->user['organisation_id'];
        $request = OrganisationMembership::find((int) $id);
        if (!$request || (int) $request['organisation_id'] !== $orgId || ($request['role_slug'] ?? '') !== 'student') {
            flashError('That student request was not found.');
            redirect('/account/trainer-requests');
        }
        $raw = json_decode((string) ($request['category_ids'] ?? ''), true);
        $request['category_ids'] = is_array($raw) ? array_map('intval', $raw) : [];

        $person = User::find((int) $request['user_id']);
        $forms = [];
        foreach (Form::forRole((int) ($person['role_id'] ?? 0), false) as $form) {
            $form['fields'] = FormField::forForm((int) $form['id']);
            $form['response'] = FormResponse::findForUserForm((int) $person['id'], (int) $form['id']);
            $forms[] = $form;
        }

        $this->render('account.trainer-requests.review', [
            'pageTitle' => 'Student request',
            'activeNav' => 'trainer_requests',
            'request' => $request,
            'person' => $person,
            'forms' => $forms,
            'categories' => OrganisationCategory::forOrganisation($orgId, true),
        ]);
    }

    public function updateCategories(string $id): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can change student categories.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/trainer-requests');
        }
        $ids = Request::post('category_ids', []);
        $ok = OrganisationMembership::updateApprovedCategories((int) $id, (int) $this->user['organisation_id'], is_array($ids) ? $ids : []);
        $ok ? flashSuccess('Student categories updated.') : flashError('Keep at least one category for the student.');
        redirect('/account/trainer-requests');
    }

    public function approve(string $id): void
    {
        $this->decide((int) $id, true);
    }

    public function reject(string $id): void
    {
        $this->decide((int) $id, false);
    }

    /** Approving a student request lets the org admin deselect categories the student asked for before approving. */
    public function approveStudent(string $id): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can approve students.');
            redirect('/account/trainer-requests');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/trainer-requests');
        }

        $categoryIds = Request::post('category_ids', []);
        $categoryIds = is_array($categoryIds) ? array_map('intval', $categoryIds) : [];

        $ok = OrganisationMembership::approveWithCategories(
            (int) $id,
            (int) $this->user['organisation_id'],
            (int) $this->user['id'],
            $categoryIds
        );

        if (!$ok) {
            flashError('That student request could not be updated.');
            redirect('/account/trainer-requests');
        }

        flashSuccess('Student approved for the categories selected.');
        redirect('/account/trainer-requests');
    }

    private function decide(int $id, bool $approve): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can approve trainers.');
            redirect('/account/trainer-requests');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/trainer-requests');
        }

        $orgId = (int) $this->user['organisation_id'];
        $ok = $approve
            ? OrganisationMembership::approve($id, $orgId, (int) $this->user['id'])
            : OrganisationMembership::reject($id, $orgId, (int) $this->user['id']);

        if (!$ok) {
            flashError('That trainer request could not be updated.');
            redirect('/account/trainer-requests');
        }

        flashSuccess($approve
            ? 'Trainer approved. They are now assigned to your organisation.'
            : 'Trainer request rejected.');
        redirect('/account/trainer-requests');
    }
}
