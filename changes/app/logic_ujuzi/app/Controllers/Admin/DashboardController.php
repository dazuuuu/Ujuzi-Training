<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\View;
use App\Models\AttachmentApplication;
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
        $attachmentStats = [];
        try {
            $attachmentStats = AttachmentApplication::countByStatus();
        } catch (\Throwable $e) {
            $attachmentStats = [];
        }

        $attachmentRecent = [];
        try {
            $attachmentRecent = AttachmentApplication::recent(8);
        } catch (\Throwable $e) {
            $attachmentRecent = [];
        }

        $attachmentOrgCount = 0;
        $branchCount = 0;
        $branchAdminCount = 0;
        try {
            $attachmentOrgCount = (int) Database::connection()->query(
                "SELECT COUNT(DISTINCT organisation_id) FROM organisation_memberships m
                 INNER JOIN users u ON u.id = m.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE m.status = 'approved' AND r.slug = 'attachment_trainer'"
            )->fetchColumn();
            $branchCount = (int) Database::connection()->query('SELECT COUNT(*) FROM organisation_branches')->fetchColumn();
            $branchAdminCount = (int) Database::connection()->query(
                "SELECT COUNT(*) FROM organisation_branches WHERE branch_admin_user_id IS NOT NULL"
            )->fetchColumn();
        } catch (\Throwable $e) {
            // Leave counts at zero if the schema isn't migrated yet.
        }

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
            'attachmentStats' => $attachmentStats,
            'attachmentRecent' => $attachmentRecent,
            'attachmentOrgCount' => $attachmentOrgCount,
            'branchCount' => $branchCount,
            'branchAdminCount' => $branchAdminCount,
        ]);
    }
}
