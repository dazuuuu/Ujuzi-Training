<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Course;
use App\Services\Notifier;

/** Super Admin's approvals: every new course before students see it, and branch deletions asked for by organisation heads. */
class CourseApprovalController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.course-approvals.index', [
            'pageTitle' => 'Approvals',
            'activeNav' => 'course-approvals',
            'courses' => Course::pendingApproval(),
            'deletions' => \App\Models\BranchDeletionRequest::pending(),
        ]);
    }

    /** Approves a branch deletion (the organisation head already asked), deleting the branch, or declines it. */
    public function branchDeletion(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/course-approvals');
        }
        $request = \App\Models\BranchDeletionRequest::find((int) $id);
        if (!$request || $request['status'] !== 'pending') {
            flashError('That request was already handled.');
            redirect('/admin/course-approvals');
        }
        $approve = Request::post('decision') === 'approved';
        \App\Models\BranchDeletionRequest::decide((int) $id, $approve, (int) $this->admin['id']);
        if ($approve) {
            \App\Models\OrganisationBranch::delete((int) $request['branch_id']);
        }
        Notifier::toUser((int) $request['requested_by_user_id'], $approve ? 'Branch deleted' : 'Branch deletion declined', $approve ? 'Branch deleted' : 'Branch deletion declined',
            $approve ? 'Super Admin approved your request, and the branch has been deleted.' : 'Super Admin declined your request to delete a branch, so it stays.', 'Open branches', '/account/branches');
        flashSuccess($approve ? 'Branch deleted.' : 'Deletion declined — the branch stays.');
        redirect('/admin/course-approvals#branch-deletions');
    }

    /** Everything in the course, read-only, so it can be checked before approving. */
    public function preview(string $id): void
    {
        $course = Course::find((int) $id);
        if (!$course) {
            flashError('That course could not be found.');
            redirect('/admin/course-approvals');
        }
        View::render('admin.course-approvals.preview', [
            'pageTitle' => 'Preview: ' . $course['title'],
            'activeNav' => 'course-approvals',
            'course' => $course,
            'modules' => \App\Models\CourseModule::forCourse((int) $course['id']),
        ]);
    }

    public function decide(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/course-approvals');
        }
        $course = Course::find((int) $id);
        $decision = (string) Request::post('decision', '');
        if (!$course || !in_array($decision, ['approved', 'rejected'], true)) {
            flashError('That course could not be found.');
            redirect('/admin/course-approvals');
        }
        $note = trim((string) Request::post('note', ''));
        Course::setApproval((int) $course['id'], $decision, $note !== '' ? mb_substr($note, 0, 500) : null);
        $approved = $decision === 'approved';
        Notifier::toUser(
            (int) $course['trainer_user_id'],
            ($approved ? 'Approved: ' : 'Changes needed: ') . $course['title'],
            $approved ? 'Your course is approved' : 'Your course needs changes',
            $approved
                ? $course['title'] . ' was approved and students can now see it.'
                : $course['title'] . ' was not approved yet' . ($note !== '' ? ': ' . $note : '.') . ' Make the changes and it will be reviewed again.',
            'Open the course',
            '/account/courses/' . (int) $course['id']
        );
        flashSuccess($approved ? 'Course approved — students can see it now.' : 'Course sent back to the tutor with your note.');
        redirect('/admin/course-approvals');
    }
}
