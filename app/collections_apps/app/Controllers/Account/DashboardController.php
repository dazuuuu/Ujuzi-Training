<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\AccountRedirect;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\OrganisationBranch;
use App\Models\OrganisationMembership;
use App\Models\User;

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
        $attachmentTrainers = [];
        try {
            if (($this->user['role_slug'] ?? '') === 'organisation_admin' && !empty($this->user['organisation_id'])) {
                $pendingTrainerRequests = OrganisationMembership::pendingTrainersForOrganisation((int) $this->user['organisation_id']);
            }
            if (OrganisationMembership::isTrainerRole((string) ($this->user['role_slug'] ?? ''))) {
                $memberships = OrganisationMembership::forUser((int) $this->user['id']);
            }
            if (Authz::isStudent($this->user)) {
                $orgIds = Authz::learnerOrganisationIds($this->user);
                $learnerCourses = Course::forLearner($orgIds);
                $enrolledIds = CourseEnrollment::idsForUser((int) $this->user['id']);
                $enrolledLookup = array_fill_keys($enrolledIds, true);
                foreach ($learnerCourses as &$course) {
                    $course['is_enrolled'] = !empty($enrolledLookup[(int) $course['id']]);
                }
                unset($course);
                foreach ($orgIds as $orgId) {
                    $learnerBranches = array_merge($learnerBranches, OrganisationBranch::forOrganisation($orgId));
                }
                $completedCourses = Course::completedByLearner($this->user);
                if ($completedCourses) {
                    $attachmentTrainers = User::attachmentTrainersForOrganisations($orgIds);
                    foreach ($attachmentTrainers as &$trainer) {
                        $trainer['attachment_duration'] = User::attachmentDuration($trainer);
                    }
                    unset($trainer);
                }
            }
        } catch (\Throwable $e) {
            $pendingTrainerRequests = [];
            $memberships = [];
            $learnerCourses = [];
            $learnerBranches = [];
            $completedCourses = [];
            $attachmentTrainers = [];
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
            'attachmentTrainers' => $attachmentTrainers,
            'isStudent' => Authz::isStudent($this->user),
            'needsProfile' => AccountRedirect::needsProfile($this->user),
        ]);
    }
}
