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
                $audienceCount = count(array_filter(AttachmentAudience::statusesFor((int) $this->user['id']), static fn(string $st): bool => $st === 'approved'));
            }
        } catch (\Throwable $e) {
            $studentCategories = [];
        }

        if (Authz::isStudent($this->user)) {
            $this->renderStudent($learnerCourses, $completedCourses);
            return;
        }

        $this->render('account.dashboard', [
            'cards' => $this->roleCards($completed, count($forms), $managedUsers, $pendingTrainerRequests, $memberships),
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
     * The small cards at the top of a staff dashboard, per role:
     * [icon, label, value, link, is it asking for attention].
     */
    private function roleCards(int $formsDone, int $formsTotal, array $managedUsers, array $pendingTrainerRequests, array $memberships): array
    {
        $slug = (string) ($this->user['role_slug'] ?? '');
        $userId = (int) $this->user['id'];
        $orgId = (int) ($this->user['organisation_id'] ?? 0);
        $cards = [];
        try {
            if ($slug === 'organisation_admin' && $orgId > 0) {
                $pendingStudents = count(OrganisationMembership::pendingStudentsForOrganisation($orgId));
                $partnersWaiting = AttachmentAudience::pendingCountForOrganisation($orgId);
                $courses = Course::forOrganisation($orgId);
                $cards[] = ['inbox', 'Requests waiting', (string) (count($pendingTrainerRequests) + $pendingStudents), '/account/trainer-requests', count($pendingTrainerRequests) + $pendingStudents > 0];
                $cards[] = ['briefcase', 'Attachment partners', $partnersWaiting > 0 ? $partnersWaiting . ' waiting' : 'Up to date', '/account/attachment-partners', $partnersWaiting > 0];
                $cards[] = ['book', 'Courses', (string) count($courses), '/account/courses', false];
                $cards[] = ['users', 'People', (string) count($managedUsers), '/account/people', false];
                $cards[] = ['search', 'Student lookup', 'By reg. no.', '/account/student-lookup', false];
            } elseif ($slug === 'trainer') {
                $approved = array_values(array_filter($memberships, static fn(array $m): bool => $m['status'] === 'approved'));
                $cards[] = ['book', 'My courses', (string) count(Course::forTrainer($userId)), '/account/courses', false];
                $cards[] = ['building', 'Organisation', $approved ? (string) $approved[0]['organisation_name'] : 'Waiting for approval', '/account/profile', !$approved];
                $cards[] = ['wallet', 'Earnings', 'Ksh ' . number_format(\App\Services\WalletService::totalsFor($userId, 'earning'), 0), '/account/wallet', false];
                $missing = \App\Models\User::missingPublicProfile($this->user);
                $cards[] = ['user', 'What students see', $missing ? 'Incomplete' : 'Complete', '/account/profile#public-profile', (bool) $missing];
            } elseif ($slug === 'attachment_trainer') {
                $statuses = AttachmentAudience::statusesFor($userId);
                $approved = count(array_filter($statuses, static fn(string $st): bool => $st === 'approved'));
                $waiting = count(array_filter($statuses, static fn(string $st): bool => $st === 'pending'));
                $cards[] = ['target', 'Where you appear', $approved . ' approved' . ($waiting ? ' · ' . $waiting . ' waiting' : ''), '/account/attachment-audience', $approved === 0];
                $cards[] = ['building', 'Organisation', $orgId > 0 ? (string) ($this->user['organisation_name'] ?? 'Registered') : 'Not registered', '/account/organisation', $orgId < 1];
                $cards[] = ['map-pin', 'Branches', (string) count(OrganisationBranch::forAttachmentProvider($userId)), '/account/branches', false];
                $cards[] = ['users', 'Attachees', (string) count($managedUsers), '/account/people', false];
                $cards[] = ['search', 'Student lookup', 'By reg. no.', '/account/student-lookup', false];
            } elseif ($slug === 'course_branch_admin') {
                $count = 0;
                foreach (OrganisationBranch::forBranchAdmin($userId) as $branch) {
                    $count += count(OrganisationMembership::pendingStudentsForBranch((int) $branch['id']));
                }
                $cards[] = ['inbox', 'Branch requests', (string) $count, '/account/course-branch-admin', $count > 0];
                $cards[] = ['folder', 'Categories', 'Manage', '/account/categories', false];
                $cards[] = ['search', 'Student lookup', 'By reg. no.', '/account/student-lookup', false];
            } elseif ($slug === 'branch_admin') {
                $counts = ['waiting' => 0, 'current' => 0, 'done' => 0];
                foreach (OrganisationBranch::forBranchAdmin($userId) as $branch) {
                    foreach (\App\Models\AttachmentApplication::forBranch((int) $branch['id']) as $application) {
                        $status = $application['status'];
                        if (in_array($status, ['pending', 'paused'], true)) {
                            $counts['waiting']++;
                        } elseif ($status === 'accepted') {
                            $counts['current']++;
                        } elseif (in_array($status, ['completed', 'recommended'], true)) {
                            $counts['done']++;
                        }
                    }
                }
                $cards[] = ['inbox', 'Waiting for you', (string) $counts['waiting'], '/account/branch-admin', $counts['waiting'] > 0];
                $cards[] = ['graduation', 'Current attachees', (string) $counts['current'], '/account/branch-admin', false];
                $cards[] = ['award', 'Completed', (string) $counts['done'], '/account/branch-admin', false];
                $cards[] = ['search', 'Student lookup', 'By reg. no.', '/account/student-lookup', false];
            }
        } catch (\Throwable $e) {
            // A card that can't load is left out rather than breaking the page.
        }
        if ($formsTotal > 0) {
            $cards[] = ['file', 'Profile forms', $formsDone . ' / ' . $formsTotal . ' done', '/account/profile', $formsDone < $formsTotal];
        }
        return $cards;
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
    private function renderStudent(array $learnerCourses, array $completedCourses): void
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

        // Wallet warnings: courses whose next module costs more coins than the wallet holds.
        $walletShort = [];
        try {
            foreach (($fees['items'] ?? []) as $item) {
                if ($item['fee'] <= 0 || $item['balance'] <= 0) {
                    continue;
                }
                $course = Course::find((int) $item['course_id']);
                if (!$course || !\App\Services\ModuleAccess::chargesPerModule($userId, $course)) {
                    continue;
                }
                $plan = \App\Services\ModuleAccess::plan($userId, $course, count(\App\Models\CourseModule::forCourse((int) $course['id'])));
                if ($plan['from_wallet'] > $wallet['balance'] + 0.001) {
                    $walletShort[] = ['course_id' => (int) $course['id'], 'title' => $course['title'], 'needs' => $plan['from_wallet']];
                }
            }
        } catch (\Throwable $e) {
            $walletShort = [];
        }

        // Courses the student can still enrol in: the newest, and the most taken.
        $open = array_values(array_filter($learnerCourses, static fn(array $c): bool => empty($c['is_enrolled'])));
        $counts = Course::enrolmentCounts(array_column($learnerCourses, 'id'));
        foreach ($open as &$course) {
            $course['enrolled_count'] = $counts[(int) $course['id']] ?? 0;
        }
        unset($course);
        $newCourses = $open;
        usort($newCourses, static fn(array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']));
        $popular = array_values(array_filter($open, static fn(array $c): bool => $c['enrolled_count'] > 0));
        usort($popular, static fn(array $a, array $b): int => $b['enrolled_count'] <=> $a['enrolled_count']);

        $this->render('account.dashboard-student', [
            'newCourses' => array_slice($newCourses, 0, 8),
            'popularCourses' => array_slice($popular, 0, 8),
            'registrationNumber' => \App\Models\User::assignRegistrationNumber($userId),
            'walletShort' => $walletShort,
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'wallet' => $wallet,
            'fees' => $fees,
            'applications' => $applications,
            'unreadNotes' => array_sum($unread),
            'certificateReady' => $completedCourses !== [],
            'needsProfile' => AccountRedirect::needsProfile($this->user),
            'minPaymentPercent' => \App\Services\WalletService::minPaymentPercent(),
        ]);
    }
}
