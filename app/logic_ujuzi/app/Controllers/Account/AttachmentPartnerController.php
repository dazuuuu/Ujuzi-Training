<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentAudience;

/**
 * An organisation providing courses answers organisations providing
 * attachment that asked to take its students. Once approved for a category,
 * the provider appears to the students enrolled in that category's courses.
 */
class AttachmentPartnerController extends BaseAccountController
{
    public function index(): void
    {
        $orgId = $this->requireOrganisationAdmin();
        $requests = AttachmentAudience::requestsForOrganisation($orgId);

        $this->render('account.attachment-partners.index', [
            'pageTitle' => 'Attachment partners',
            'activeNav' => 'attachment_partners',
            'pending' => array_values(array_filter($requests, static fn(array $r): bool => $r['counts']['pending'] > 0)),
            'partners' => array_values(array_filter($requests, static fn(array $r): bool => $r['counts']['pending'] === 0 && $r['counts']['approved'] > 0)),
            'declined' => array_values(array_filter($requests, static fn(array $r): bool => $r['counts']['pending'] === 0 && $r['counts']['approved'] === 0)),
        ]);
    }

    public function decide(string $providerId, string $decision): void
    {
        $orgId = $this->requireOrganisationAdmin();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/attachment-partners');
        }
        if (!in_array($decision, ['approve', 'reject'], true)) {
            redirect('/account/attachment-partners');
        }

        $categoryIds = [];
        if ($decision === 'approve') {
            $posted = Request::post('category_ids', []);
            $categoryIds = is_array($posted) ? array_map('intval', $posted) : [];
            if (!$categoryIds) {
                flashError('Tick at least one category to approve, or decline the request.');
                redirect('/account/attachment-partners');
            }
        }

        if (!AttachmentAudience::decide($orgId, (int) $providerId, $categoryIds, (int) $this->user['id'])) {
            flashError('That request could not be found.');
            redirect('/account/attachment-partners');
        }
        flashSuccess($decision === 'approve'
            ? 'Approved. Your students in the ticked categories now see this organisation under Attachment.'
            : 'Declined. Your students won\'t see this organisation.');
        redirect('/account/attachment-partners');
    }

    private function requireOrganisationAdmin(): int
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisations providing courses approve attachment partners.');
            redirect('/account/dashboard');
        }
        return (int) $this->user['organisation_id'];
    }
}
