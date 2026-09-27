<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\AccountRedirect;
use App\Models\AttachmentAudience;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;
use App\Models\StoreSetting;

class DashboardController extends BaseAccountController
{
    public function index(): void
    {
        $forms = Form::forRole((int) $this->user['role_id'], true);
        $responses = FormResponse::forUser((int) $this->user['id']);
        $completed = count(array_filter($responses, fn(array $row): bool => !empty($row['submitted_at'])));

        $managedUsers = [];
        if (Authz::canManageUsers($this->user)) {
            try {
                $managedUsers = Authz::scopedUsers($this->user);
            } catch (\Throwable $e) {
                $managedUsers = [];
            }
        }

        $pendingTrainerRequests = [];
        $memberships = [];
        $learnerCourses = [];
        $learnerBranches = [];
        $completedCourses = [];
        $isEnrolledInAnyCourse = false;
        try {
            if (($this->user['role_slug'] ?? '') === 'organisation_admin' && !empty($this->user['organisation_id'])) {
                $pendingTrainerRequests = OrganisationMembership::pendingTrainersForOrganisation((int) $this->user['organisation_id']);
            }
            if (OrganisationMembership::isTrainerRole((string) ($this->user['role_slug'] ?? ''))) {
                $memberships = OrganisationMembership::forUser((int) $this->user['id']);
            }
            if (Authz::isStudent($this->user)) {
                $orgIds = Authz::learnerOrganisationIds($this->user);
                $learnerCourses = Course::forLearner($orgIds, Authz::approvedCategoryIds($this->user));
                $enrolledIds = CourseEnrollment::idsForUser((int) $this->user['id']);
                $enrolledLookup = array_fill_keys($enrolledIds, true);
                foreach ($learnerCourses as &$course) {
                    $course['is_enrolled'] = !empty($enrolledLookup[(int) $course['id']]);
                }
                unset($course);
                foreach ($orgIds as $orgId) {
                    $learnerBranches = array_merge($learnerBranches, OrganisationBranch::forOrganisation($orgId));
                }
                $completedCourses = Course::certifiableCompletedByLearner($this->user);
                $isEnrolledInAnyCourse = $enrolledIds !== [];
            }
        } catch (\Throwable $e) {
            $pendingTrainerRequests = [];
            $memberships = [];
            $learnerCourses = [];
            $learnerBranches = [];
            $completedCourses = [];
            $isEnrolledInAnyCourse = false;
        }

        $studentCategories = [];
        $audienceCount = null;
        try {
            if (Authz::isStudent($this->user)) {
                $studentCategories = $this->studentCategoryGroups();
            }
            if (($this->user['role_slug'] ?? '') === 'attachment_trainer') {
                $audienceCount = count(AttachmentAudience::categoryIdsFor((int) $this->user['id']));
            }
        } catch (\Throwable $e) {
            $studentCategories = [];
        }

        if (Authz::isStudent($this->user)) {
            $this->renderStudent($studentCategories, $completedCourses);
            return;
        }

        $this->render('account.dashboard', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'forms' => $forms,
            'completedForms' => $completed,
            'totalForms' => count($forms),
            'managedUsers' => $managedUsers,
            'recentManaged' => array_slice($managedUsers, 0, 6),
            'pendingTrainerRequests' => $pendingTrainerRequests,
            'memberships' => $memberships,
            'learnerCourses' => $learnerCourses,
            'learnerBranches' => $learnerBranches,
            'completedCourses' => $completedCourses,
            'isEnrolledInAnyCourse' => $isEnrolledInAnyCourse,
            'studentCategories' => $studentCategories,
            'audienceCount' => $audienceCount,
            'isStudent' => Authz::isStudent($this->user),
            'needsProfile' => AccountRedirect::needsProfile($this->user),
            'paymentsEnabled' => StoreSetting::get('course_payments_enabled', '0') === '1',
        ]);
    }

    /**
     * The categories the student picked, per organisation they asked to join:
     * approved ones are what the branch granted, pending ones what they asked for.
     */
    private function studentCategoryGroups(): array
    {
        $memberships = OrganisationMembership::forUser((int) $this->user['id']);
        $allIds = [];
        foreach ($memberships as $membership) {
            array_push($allIds, ...$membership['category_ids']);
        }
        $names = OrganisationCategory::namesByIds($allIds);

        $groups = [];
        foreach ($memberships as $membership) {
            if (!in_array($membership['status'], ['approved', 'pending'], true)) {
                continue;
            }
            $categories = [];
            foreach ($membership['category_ids'] as $id) {
                if (isset($names[$id])) {
                    $categories[] = $names[$id];
                }
            }
            $groups[] = [
                'organisation_name' => $membership['organisation_name'],
                'branch_title' => $membership['branch_title'] ?? null,
                'status' => $membership['status'],
                'categories' => $categories,
            ];
        }
        return $groups;
    }

    /**
     * The student's own dashboard: wallet, fees still owed, the organisation
     * that approved them with their categories, progress on each course they
     * enrolled for, and their attachment.
     */
    private function renderStudent(array $categoryGroups, array $completedCourses): void
    {
        $userId = (int) $this->user['id'];
        $wallet = ['balance' => 0.0, 'deposited' => 0.0, 'spent' => 0.0];
        $fees = null;
        $applications = [];
        $unread = [];
        try {
            $wallet = [
                'balance' => \App\Services\WalletService::balanceKsh($userId),
                'deposited' => \App\Services\WalletService::totalsFor($userId, 'deposit'),
                'spent' => \App\Services\WalletService::totalsFor($userId, 'course_payment'),
            ];
            $fees = \App\Services\WalletService::feeSummaries([$userId])[$userId] ?? null;
            $applications = \App\Models\AttachmentApplication::allForStudent($userId);
            $unread = \App\Models\AttachmentMessage::unreadCounts(array_column($applications, 'id'), \App\Models\AttachmentMessage::SIDE_STUDENT);
        } catch (\Throwable $e) {
            // Parts that can't load are shown empty rather than breaking the page.
        }

        $this->render('account.dashboard-student', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'wallet' => $wallet,
            'fees' => $fees,
            'approvedGroups' => array_values(array_filter($categoryGroups, static fn(array $g): bool => $g['status'] === 'approved')),
            'pendingGroups' => array_values(array_filter($categoryGroups, static fn(array $g): bool => $g['status'] === 'pending')),
            'applications' => $applications,
            'unreadNotes' => array_sum($unread),
            'certificateReady' => $completedCourses !== [],
            'needsProfile' => AccountRedirect::needsProfile($this->user),
            'minPaymentPercent' => \App\Services\WalletService::minPaymentPercent(),
        ]);
    }
}
