<?php

namespace App\Controllers\Admin;

use App\Core\View;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Services\MigrationService;

class DashboardController extends BaseAdminController
{
    public function index(): void
    {
        $roleCounts = User::countByRole();
        $completion = FormResponse::completionStats();
        $stats = [
            'users' => User::count(),
            'organisations' => Organisation::count(),
            'forms' => Form::count(),
            'roles' => Role::count(),
            'profilesCompleted' => $completion['completed'],
            'profilesTotal' => $completion['total'],
            'profileRate' => $completion['total'] > 0
                ? round(($completion['completed'] / $completion['total']) * 100, 1)
                : 0,
        ];

        $pending = [];
        try {
            $pending = MigrationService::pending();
        } catch (\Throwable $e) {
            $pending = [];
        }

        View::render('admin.dashboard', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'stats' => $stats,
            'roleCounts' => $roleCounts,
            'recentUsers' => User::recent(8),
            'pendingMigrations' => $pending,
        ]);
    }
}
