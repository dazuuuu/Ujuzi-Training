<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\AttachmentApplication;
use App\Services\AttachmentReport;
use App\Services\WalletService;

/**
 * Super Admin's view of every attachment request — requested, on hold,
 * accepted, completed and declined — with an Excel export of the same list,
 * optionally limited to the dates the requests were sent.
 */
class AttachmentController extends BaseAdminController
{
    public const TABS = [
        '' => 'All',
        AttachmentApplication::STATUS_PENDING => 'Requested',
        AttachmentApplication::STATUS_PAUSED => 'On hold',
        AttachmentApplication::STATUS_ACCEPTED => 'Accepted',
        AttachmentApplication::STATUS_COMPLETED => 'Completed',
        AttachmentApplication::STATUS_REJECTED => 'Declined',
    ];

    public function index(): void
    {
        [$status, $query, $from, $to] = $this->filters();
        $applications = AttachmentApplication::adminList($status, $query, $from, $to);

        View::render('admin.attachments.index', [
            'pageTitle' => 'Attachments',
            'activeNav' => 'attachments',
            'applications' => $applications,
            'fees' => WalletService::feeSummaries(array_column($applications, 'student_user_id')),
            'counts' => AttachmentApplication::countByStatus(),
            'status' => $status,
            'query' => $query,
            'from' => $from,
            'to' => $to,
            'tabs' => self::TABS,
        ]);
    }

    public function export(): void
    {
        [$status, $query, $from, $to] = $this->filters();
        $label = strtolower(str_replace(' ', '-', self::TABS[$status] ?? 'all'));
        AttachmentReport::download(AttachmentApplication::adminList($status, $query, $from, $to), $label, $from, $to);
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} status tab, search text, from and to dates */
    private function filters(): array
    {
        $status = (string) Request::query('status', '');
        if (!array_key_exists($status, self::TABS)) {
            $status = '';
        }
        [$from, $to] = AttachmentReport::dateRange();
        return [$status, mb_substr(trim((string) Request::query('q', '')), 0, 100), $from, $to];
    }
}
