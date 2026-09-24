<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\OrganisationBranch;

class BranchAdminController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireBranchAdmin();

        $branches = OrganisationBranch::forBranchAdmin((int) $this->user['id']);
        $applications = [];
        foreach ($branches as $branch) {
            foreach (AttachmentApplication::forBranch((int) $branch['id']) as $application) {
                $applications[] = $application;
            }
        }

        $this->render('account.branch-admin.index', [
            'pageTitle' => 'Attachees',
            'activeNav' => 'branch_admin',
            'branches' => $branches,
            'applications' => $applications,
        ]);
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
}
