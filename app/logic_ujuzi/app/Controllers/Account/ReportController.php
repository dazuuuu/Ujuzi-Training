<?php

namespace App\Controllers\Account;

use App\Services\AttachmentReport;
use App\Services\ReportCharts;
use App\Services\SpreadsheetExport;

/**
 * Visual reports for an organisation providing courses (its own courses,
 * students, payments and attachments) and an attachment provider (its own
 * requests and branches), with the same numbers downloadable as Excel.
 */
class ReportController extends BaseAccountController
{
    public function index(): void
    {
        [$from, $to] = AttachmentReport::dateRange();
        $this->render('account.reports.index', [
            'pageTitle' => 'Reports',
            'activeNav' => 'reports',
            'reportCharts' => ReportCharts::build($this->scope(), $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(): void
    {
        [$from, $to] = AttachmentReport::dateRange();
        $charts = ReportCharts::build($this->scope(), $from, $to);
        SpreadsheetExport::download(
            'report-' . ($from ?: 'start') . '-to-' . ($to ?: date('Y-m-d')),
            'Reports',
            ['Report', 'Item', 'Value'],
            ReportCharts::rows($charts)
        );
    }

    private function scope(): array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        if ($role === 'organisation_admin' && !empty($this->user['organisation_id'])) {
            return ['organisation' => (int) $this->user['organisation_id']];
        }
        if ($role === 'attachment_trainer') {
            return ['provider' => (int) $this->user['id']];
        }
        if ($role === 'course_branch_admin') {
            [$orgId, $studentIds] = \App\Services\BranchScope::courseBranch((int) $this->user['id']);
            if ($orgId) {
                return ['organisation' => $orgId, 'students' => $studentIds];
            }
        }
        if ($role === 'branch_admin') {
            return ['branches' => \App\Services\BranchScope::branchIds((int) $this->user['id'])];
        }
        flashError('Reports are available to organisations, their branch admins and attachment providers.');
        redirect('/account/dashboard');
    }
}
