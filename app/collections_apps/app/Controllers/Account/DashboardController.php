<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Models\Form;
use App\Models\FormResponse;
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
        try {
            if (($this->user['role_slug'] ?? '') === 'organisation_admin' && !empty($this->user['organisation_id'])) {
                $pendingTrainerRequests = OrganisationMembership::pendingTrainersForOrganisation((int) $this->user['organisation_id']);
            }
            if (OrganisationMembership::isTrainerRole((string) ($this->user['role_slug'] ?? ''))) {
                $memberships = OrganisationMembership::forUser((int) $this->user['id']);
            }
        } catch (\Throwable $e) {
            $pendingTrainerRequests = [];
            $memberships = [];
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
        ]);
    }
}
