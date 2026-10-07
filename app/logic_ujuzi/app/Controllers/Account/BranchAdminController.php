<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\OrganisationBranch;
use App\Services\WalletService;

class BranchAdminController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireBranchAdmin();

        $branches = OrganisationBranch::forBranchAdmin((int) $this->user['id']);
        [$from, $to] = \App\Services\AttachmentReport::dateRange();
        $applications = [];
        foreach ($branches as $branch) {
            foreach (AttachmentApplication::forBranch((int) $branch['id']) as $application) {
                // Optional date filter on when the student sent the request.
                $day = substr((string) ($application['selected_at'] ?? ''), 0, 10);
                if (($from !== '' && $day < $from) || ($to !== '' && $day > $to)) {
                    continue;
                }
                $applications[] = $application;
            }
        }

        // Every student's course-fee standing, so the admin sees it before
        // accepting them or marking them completed.
        $fees = [];
        try {
            $fees = WalletService::feeSummaries(array_column($applications, 'student_user_id'));
        } catch (\Throwable $e) {
            $fees = [];
        }

        $active = [];
        $completed = [];
        foreach ($applications as $application) {
            $application['fees'] = $fees[(int) $application['student_user_id']] ?? null;
            $application['request_fees'] = WalletService::requestFees((int) $application['student_user_id'], $application['category_id'] ?? null, !empty($application['course_id']) ? (int) $application['course_id'] : null);
            if (in_array($application['status'], [AttachmentApplication::STATUS_COMPLETED, AttachmentApplication::STATUS_RECOMMENDED], true)) {
                $completed[] = $application;
            } else {
                $active[] = $application;
            }
        }

        $this->render('account.branch-admin.index', [
            'pageTitle' => 'Attachees',
            'activeNav' => 'branch_admin',
            'branches' => $branches,
            'activeApplications' => $active,
            'completedApplications' => $completed,
            'unreadNotes' => $this->unreadNotes($applications),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** Accepts every ticked student at once — requests that are waiting or on hold. */
    public function bulkAccept(): void
    {
        $this->requireBranchAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/branch-admin');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) Request::post('ids', [])))));
        if (!$ids) {
            flashError('Tick at least one student to accept.');
            redirect('/account/branch-admin');
        }
        $branchIds = array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin((int) $this->user['id']));
        $accepted = 0;
        foreach ($ids as $id) {
            foreach ($branchIds as $branchId) {
                $application = AttachmentApplication::findForBranch($id, $branchId);
                if ($application && in_array($application['status'], [AttachmentApplication::STATUS_PENDING, AttachmentApplication::STATUS_PAUSED], true)) {
                    AttachmentApplication::setStatus($id, AttachmentApplication::STATUS_ACCEPTED);
                    $accepted++;
                    break;
                }
            }
        }
        $skipped = count($ids) - $accepted;
        $accepted
            ? flashSuccess('Accepted ' . $accepted . ' student' . ($accepted === 1 ? '' : 's') . '.' . ($skipped ? ' ' . $skipped . ' could not be accepted (already decided or not in your branch).' : ''))
            : flashError('None of those students could be accepted — they were already decided or are not in your branch.');
        redirect('/account/branch-admin');
    }

    /** Excel download of this admin's branches' requests, optionally for the dates the students sent them. */
    public function export(): void
    {
        $this->requireBranchAdmin();
        [$from, $to] = \App\Services\AttachmentReport::dateRange();
        $branchIds = array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin((int) $this->user['id']));
        \App\Services\AttachmentReport::download(
            AttachmentApplication::report(['branch_ids' => $branchIds, 'from' => $from, 'to' => $to]),
            'branch',
            $from,
            $to
        );
    }

    public function accept(string $id): void
    {
        $this->decide((int) $id, AttachmentApplication::STATUS_ACCEPTED, [AttachmentApplication::STATUS_PENDING]);
    }

    public function complete(string $id): void
    {
        // Completing at the branch is the final approval — it generates the
        // student's recommendation letter, no separate org-level certify step.
        $this->decide((int) $id, AttachmentApplication::STATUS_RECOMMENDED, [AttachmentApplication::STATUS_ACCEPTED]);
    }

    private function decide(int $id, string $newStatus, array $fromStatuses): void
    {
        $this->requireBranchAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/branch-admin');
        }

        $branchIds = array_map(
            static fn(array $branch): int => (int) $branch['id'],
            OrganisationBranch::forBranchAdmin((int) $this->user['id'])
        );

        $application = null;
        foreach ($branchIds as $branchId) {
            $application = AttachmentApplication::findForBranch($id, $branchId);
            if ($application) {
                break;
            }
        }

        if (!$application || !in_array($application['status'], $fromStatuses, true)) {
            flashError('That attachee could not be updated.');
            redirect('/account/branch-admin');
        }

        if ($newStatus === AttachmentApplication::STATUS_RECOMMENDED && !\App\Models\AttachmentAssessment::isMarked($id)) {
            flashError(\App\Models\AttachmentAssessment::NOT_MARKED);
            redirect('/account/attachment-requests/' . $id . '#assessment');
        }
        if ($newStatus === AttachmentApplication::STATUS_RECOMMENDED) {
            $owed = WalletService::requestFees((int) $application['student_user_id'], !empty($application['category_id']) ? (int) $application['category_id'] : null, !empty($application['course_id']) ? (int) $application['course_id'] : null)['balance'];
            if ($owed > 0) {
                flashError(WalletService::outstandingMessage($owed));
                redirect('/account/branch-admin');
            }
        }

        AttachmentApplication::setStatus($id, $newStatus);
        $labels = [
            AttachmentApplication::STATUS_ACCEPTED => 'Attachee accepted.',
            AttachmentApplication::STATUS_RECOMMENDED => 'Attachment completed. Their recommendation letter is ready in their portal.',
        ];
        flashSuccess($labels[$newStatus] ?? 'Attachee updated.');
        redirect('/account/branch-admin');
    }

    private function requireBranchAdmin(): void
    {
        $isActive = (string) ($this->user['account_status'] ?? 'active') === 'active';
        if (($this->user['role_slug'] ?? '') !== 'branch_admin' || !$isActive) {
            flashError('Only active branch admins can access this page.');
            redirect('/account/dashboard');
        }
    }

    /** application id => notes from the student not yet read by the branch. */
    private function unreadNotes(array $applications): array
    {
        try {
            return \App\Models\AttachmentMessage::unreadCounts(array_column($applications, 'id'), \App\Models\AttachmentMessage::SIDE_REVIEWER);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
