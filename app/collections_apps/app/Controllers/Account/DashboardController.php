<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Models\Course;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\OrganisationBranch;
use App\Models\OrganisationMembership;

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
                foreach ($orgIds as $orgId) {
                    $learnerBranches = array_merge($learnerBranches, OrganisationBranch::forOrganisation($orgId));
                }
            }
        } catch (\Throwable $e) {
            $pendingTrainerRequests = [];
            $memberships = [];
            $learnerCourses = [];
            $learnerBranches = [];
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
            'isStudent' => Authz::isStudent($this->user),
        ]);
    }
}
