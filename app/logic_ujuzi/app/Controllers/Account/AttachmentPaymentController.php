<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\OrganisationBranch;
use App\Services\AttachmentReport;

/**
 * Students who completed their attachment, to process payments: a branch
 * admin sees their own branches; the organisation providing attachment sees
 * every branch, or one at a time. Downloads as Excel or opens as a PDF to
 * view and print.
 */
class AttachmentPaymentController extends BaseAccountController
{
    public function index(): void
    {
        [$branches, $filters, $branchId] = $this->scope();
        [$from, $to] = AttachmentReport::dateRange();
        $applications = AttachmentReport::completed($filters + ['from' => $from, 'to' => $to, 'state' => AttachmentReport::paymentState()]);
        $this->render('account.attachment-payments.index', [
            'pageTitle' => 'Attachment payments',
            'activeNav' => 'attachment_payments',
            'applications' => $applications,
            'rows' => AttachmentReport::paymentRows($applications),
            'perBranch' => AttachmentReport::perBranch($applications),
            'branches' => $branches,
            'branchId' => $branchId,
            'from' => $from,
            'to' => $to,
            'state' => AttachmentReport::paymentState(),
            'baseUrl' => '/account/attachment-payments',
        ]);
    }

    public function export(string $format): void
    {
        [$branches, $filters, $branchId] = $this->scope();
        [$from, $to] = AttachmentReport::dateRange();
        $title = 'All branches';
        foreach ($branches as $branch) {
            if ((int) $branch['id'] === $branchId) {
                $title = (string) $branch['title'];
            }
        }
        AttachmentReport::downloadPayments(
            AttachmentReport::completed($filters + ['from' => $from, 'to' => $to, 'state' => AttachmentReport::paymentState()]),
            $title,
            $format === 'pdf' ? 'pdf' : 'excel',
            $from,
            $to,
            AttachmentReport::paymentState()
        );
    }

    /** [branches this user may report on, report filters, chosen branch id (0 = all)]. */
    private function scope(): array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        if ($role === 'branch_admin') {
            $branches = OrganisationBranch::forBranchAdmin((int) $this->user['id']);
            $filters = [];
        } elseif ($role === 'attachment_trainer') {
            $branches = OrganisationBranch::forAttachmentProvider((int) $this->user['id']);
            $filters = ['provider_id' => (int) $this->user['id']];
        } else {
            flashError('Attachment payment lists are for organisations providing attachment and their branch admins.');
            redirect('/account/dashboard');
        }
        $ids = array_map(static fn(array $b): int => (int) $b['id'], $branches);
        $branchId = (int) Request::query('branch', 0);
        if (!in_array($branchId, $ids, true)) {
            $branchId = 0;
        }
        if ($branchId) {
            $filters['branch_ids'] = [$branchId];
        } elseif ($role === 'branch_admin') {
            $filters['branch_ids'] = $ids; // never another branch's students
        }
        return [$branches, $filters, $branchId];
    }
}
