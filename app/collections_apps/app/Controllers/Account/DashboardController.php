<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Models\Form;
use App\Models\FormResponse;

class DashboardController extends BaseAccountController
{
    public function index(): void
    {
        $forms = Form::forRole((int) $this->user['role_id'], true);
        $responses = FormResponse::forUser((int) $this->user['id']);
        $completed = count(array_filter($responses, fn(array $row): bool => !empty($row['submitted_at'])));

        $managedUsers = Authz::canManageUsers($this->user)
            ? Authz::scopedUsers($this->user)
            : [];

        $this->render('account.dashboard', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'forms' => $forms,
            'completedForms' => $completed,
            'totalForms' => count($forms),
            'managedUsers' => $managedUsers,
            'recentManaged' => array_slice($managedUsers, 0, 6),
        ]);
    }
}
