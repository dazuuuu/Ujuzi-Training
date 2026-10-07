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
            'charts' => $this->charts(),
        ]);
    }

    /**
     * Numbers behind the dashboard charts. Each query is optional: a part
     * that fails (e.g. before a migration) is left empty, not the page.
     */
    private function charts(): array
    {
        $pdo = Database::connection();
        $safe = static function (callable $fn, $fallback) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        // New students per month, the last 7 months (oldest first).
        $months = [];
        for ($i = 6; $i >= 0; $i--) {
            $months[date('Y-m', strtotime("first day of -$i month"))] = 0;
        }
        $monthly = $safe(static function () use ($pdo, $months): array {
            $rows = $pdo->query(
                "SELECT DATE_FORMAT(u.created_at, '%Y-%m') AS ym, COUNT(*) AS n FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'student' AND u.created_at >= '" . array_key_first($months) . "-01'
                 GROUP BY ym"
            )->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($rows as $ym => $n) {
                if (isset($months[$ym])) {
                    $months[$ym] = (int) $n;
                }
            }
            return $months;
        }, $months);

        // Course payments (Ksh) and new enrolments per day, the last 7 days.
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[date('Y-m-d', strtotime("-$i day"))] = 0;
        }
        $perDay = static function (string $sql) use ($pdo, $days, $safe): array {
            return $safe(static function () use ($pdo, $days, $sql): array {
                foreach ($pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR) as $day => $value) {
                    if (isset($days[$day])) {
                        $days[$day] = (float) $value;
                    }
                }
                return $days;
            }, $days);
        };
        $since = array_key_first($days);
        $revenue = $perDay("SELECT DATE(created_at) d, SUM(amount_ksh) FROM wallet_transactions WHERE type = 'course_payment' AND created_at >= '$since' GROUP BY d");
        $enrolments = $perDay("SELECT DATE(enrolled_at) d, COUNT(*) FROM course_enrollments WHERE enrolled_at >= '$since' GROUP BY d");

        $revenueTotal = $safe(static fn(): float => (float) $pdo->query("SELECT COALESCE(SUM(amount_ksh), 0) FROM wallet_transactions WHERE type = 'course_payment'")->fetchColumn(), 0.0);
        $deposits = $safe(static fn(): float => (float) $pdo->query("SELECT COALESCE(SUM(amount_ksh), 0) FROM wallet_transactions WHERE type = 'deposit'")->fetchColumn(), 0.0);

        // Students per organisation providing courses (approved members), top 6.
        $byOrganisation = $safe(static fn(): array => $pdo->query(
            "SELECT o.name, COUNT(DISTINCT m.user_id) AS n FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
             INNER JOIN organisations o ON o.id = m.organisation_id
             WHERE m.status = 'approved'
             GROUP BY o.id, o.name ORDER BY n DESC, o.name LIMIT 6"
        )->fetchAll(), []);

        $courses = $safe(static fn(): array => $pdo->query(
            'SELECT COUNT(*) AS total, COALESCE(SUM(is_published = 1), 0) AS published FROM courses'
        )->fetch() ?: ['total' => 0, 'published' => 0], ['total' => 0, 'published' => 0]);

        return [
            'monthly' => $monthly,
            'revenue' => $revenue,
            'enrolments' => $enrolments,
            'revenueTotal' => $revenueTotal,
            'deposits' => $deposits,
            'byOrganisation' => $byOrganisation,
            'courses' => $courses,
        ];
    }
}
