<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Models\OrganisationBranch;
use App\Services\AttachmentReport;

/**
 * Super Admin's list of completed attachments for processing payments, for
 * every organisation providing attachment or one of them, per branch.
 */
class AttachmentPaymentController extends BaseAdminController
{
    public function index(): void
    {
        [$providers, $branches, $filters, $providerId, $branchId] = $this->filters();
        [$from, $to] = AttachmentReport::dateRange();
        $applications = AttachmentReport::completed($filters + ['from' => $from, 'to' => $to, 'state' => AttachmentReport::paymentState()]);
        View::render('admin.attachment-payments.index', [
            'pageTitle' => 'Attachment payments',
            'activeNav' => 'attachments',
            'applications' => $applications,
            'rows' => AttachmentReport::paymentRows($applications),
            'perBranch' => AttachmentReport::perBranch($applications),
            'providers' => $providers,
            'providerId' => $providerId,
            'branches' => $branches,
            'branchId' => $branchId,
            'from' => $from,
            'to' => $to,
            'state' => AttachmentReport::paymentState(),
            'baseUrl' => '/admin/attachment-payments',
        ]);
    }

    public function export(string $format): void
    {
        [$providers, $branches, $filters, $providerId, $branchId] = $this->filters();
        [$from, $to] = AttachmentReport::dateRange();
        $title = $providerId ? (string) ($providers[$providerId] ?? 'Provider') : 'All providers';
        foreach ($branches as $branch) {
            if ((int) $branch['id'] === $branchId) {
                $title .= ' - ' . $branch['title'];
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

    /** [providers (user id => name), the chosen provider's branches, report filters, provider id, branch id]. */
    private function filters(): array
    {
        $providers = [];
        $stmt = Database::connection()->query(
            "SELECT u.id, COALESCE(NULLIF(o.name, ''), TRIM(CONCAT(u.first_name, ' ', u.last_name)), u.email) AS name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'attachment_trainer'
             LEFT JOIN organisations o ON o.id = u.organisation_id
             ORDER BY name"
        );
        foreach ($stmt->fetchAll() as $row) {
            $providers[(int) $row['id']] = (string) $row['name'];
        }
        $providerId = (int) Request::query('provider', 0);
        if (!isset($providers[$providerId])) {
            $providerId = 0;
        }
        $filters = $providerId ? ['provider_id' => $providerId] : [];
        $branches = $providerId ? OrganisationBranch::forAttachmentProvider($providerId) : [];
        $branchId = (int) Request::query('branch', 0);
        if (!in_array($branchId, array_map(static fn(array $b): int => (int) $b['id'], $branches), true)) {
            $branchId = 0;
        }
        if ($branchId) {
            $filters['branch_ids'] = [$branchId];
        }
        return [$providers, $branches, $filters, $providerId, $branchId];
    }
}
