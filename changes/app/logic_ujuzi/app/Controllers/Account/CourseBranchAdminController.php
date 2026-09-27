<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;
use App\Services\WalletService;

class CourseBranchAdminController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireCourseBranchAdmin();

        $branches = OrganisationBranch::forBranchAdmin((int) $this->user['id']);
        $requests = [];
        $categories = [];
        $approved = [];
        $enrollments = [];
        foreach ($branches as $branch) {
            foreach (OrganisationMembership::pendingStudentsForBranch((int) $branch['id']) as $request) {
                $requests[] = $request;
            }
            $orgId = (int) ($branch['organisation_id'] ?? 0);
            foreach (OrganisationMembership::approvedStudents($orgId, (int) $branch['id']) as $student) {
                $approved[] = $student;
            }
            if ($orgId) {
                try {
                    foreach (WalletService::branchEnrollments($orgId, (int) $branch['id']) as $enrollment) {
                        $enrollments[] = $enrollment;
                    }
                } catch (\Throwable $e) {
                    // Finance tables may not be migrated yet; the rest of the page still works.
                }
                if (!isset($categories[$orgId])) {
                    $categories[$orgId] = OrganisationCategory::forOrganisation($orgId, true);
                }
            }
        }

        $this->render('account.course-branch-admin.index', [
            'pageTitle' => 'Branch Requests',
            'activeNav' => 'course_branch_admin',
            'branches' => $branches,
            'requests' => $requests,
            'approvedStudents' => $approved,
            'enrollments' => $enrollments,
            'categoriesByOrg' => $categories,
        ]);
    }

    public function approve(string $id): void
    {
        $this->requireCourseBranchAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/course-branch-admin');
        }

        $membership = $this->ownedPendingMembership((int) $id);
        $categoryIds = Request::post('category_ids', []);
        $categoryIds = is_array($categoryIds) ? array_map('intval', $categoryIds) : [];

        OrganisationMembership::approveWithCategories(
            (int) $membership['id'],
            (int) $membership['organisation_id'],
            (int) $this->user['id'],
            $categoryIds
        );
        flashSuccess('Student approved for the categories selected.');
        redirect('/account/course-branch-admin');
    }

    public function updateCategories(string $id): void
    {
        $this->requireCourseBranchAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/course-branch-admin');
        }
        $membership = OrganisationMembership::find((int) $id);
        $branchIds = array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin((int) $this->user['id']));
        if (!$membership || !in_array((int) ($membership['branch_id'] ?? 0), $branchIds, true)) {
            flashError('That student is not in your branch.');
            redirect('/account/course-branch-admin');
        }
        $ids = Request::post('category_ids', []);
        $ok = OrganisationMembership::updateApprovedCategories((int) $id, (int) $membership['organisation_id'], is_array($ids) ? $ids : []);
        $ok ? flashSuccess('Student categories updated.') : flashError('Keep at least one category for the student.');
        redirect('/account/course-branch-admin');
    }

    public function reject(string $id): void
    {
        $this->requireCourseBranchAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/course-branch-admin');
        }

        $membership = $this->ownedPendingMembership((int) $id);
        OrganisationMembership::reject((int) $membership['id'], (int) $membership['organisation_id'], (int) $this->user['id']);
        flashSuccess('Student request rejected.');
        redirect('/account/course-branch-admin');
    }

    private function ownedPendingMembership(int $id): array
    {
        $branchIds = array_map(
            static fn(array $branch): int => (int) $branch['id'],
            OrganisationBranch::forBranchAdmin((int) $this->user['id'])
        );
        foreach ($branchIds as $branchId) {
            foreach (OrganisationMembership::pendingStudentsForBranch($branchId) as $request) {
                if ((int) $request['id'] === $id) {
                    return $request;
                }
            }
        }
        flashError('That request could not be found for your branch.');
        redirect('/account/course-branch-admin');
    }

    private function requireCourseBranchAdmin(): void
    {
        $isActive = (string) ($this->user['account_status'] ?? 'active') === 'active';
        if (($this->user['role_slug'] ?? '') !== 'course_branch_admin' || !$isActive) {
            flashError('Only active course branch admins can access this page.');
            redirect('/account/dashboard');
        }
    }
}
