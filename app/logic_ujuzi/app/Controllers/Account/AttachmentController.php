<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\OrganisationBranch;
use App\Models\User;

class AttachmentController extends BaseAccountController
{
    public function index(): void
    {
        if (!Authz::isStudent($this->user)) {
            flashError('Only students choose an attachment provider.');
            redirect('/account/dashboard');
        }

        $application = AttachmentApplication::forStudent((int) $this->user['id']);
        $this->render('account.attachment-providers.index', [
            'pageTitle' => 'Attachment',
            'activeNav' => 'attachment_providers',
            'application' => $application,
            // Attachment is open to every student — all providers are listed.
            'providers' => User::allAttachmentProviders(),
        ]);
    }

    public function select(): void
    {
        if (!Authz::isStudent($this->user)) {
            flashError('Only students can choose an attachment.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/attachment-providers');
        }

        $providerId = (int) Request::post('provider_id', 0);
        $branchId = (int) Request::post('branch_id', 0);

        $provider = null;
        foreach (User::allAttachmentProviders() as $option) {
            if ((int) $option['id'] === $providerId) {
                $provider = $option;
                break;
            }
        }
        if (!$provider) {
            flashError('Choose an attachment organisation from the list.');
            redirect('/account/attachment-providers');
        }

        // Only organisations with branches ask for one; otherwise the
        // organisation's own admin reviews the request.
        if ($provider['attachment_branches']) {
            $allowedBranchIds = array_map(
                static fn(array $branch): int => (int) ($branch['id'] ?? 0),
                $provider['attachment_branches']
            );
            if ($branchId < 1 || !in_array($branchId, $allowedBranchIds, true) || !OrganisationBranch::findActive($branchId)) {
                flashError('Choose the branch you want to go to — its admin reviews your request.');
                redirect('/account/attachment-providers');
            }
        } else {
            $branchId = 0;
        }

        if (!AttachmentApplication::saveGeneralSelection((int) $this->user['id'], $providerId, $branchId > 0 ? $branchId : null)) {
            flashError('Your attachment has already been accepted, so it can no longer be changed.');
            redirect('/account/attachment-providers');
        }

        flashSuccess($branchId > 0 ? 'Request sent. The branch admin will review and accept you.' : 'Request sent. The organisation will review and accept you.');
        redirect('/account/attachment-providers');
    }

    public function providerAccept(string $id): void
    {
        $this->providerDecide((int) $id, AttachmentApplication::STATUS_PENDING, AttachmentApplication::STATUS_ACCEPTED, 'Student accepted.');
    }

    public function providerComplete(string $id): void
    {
        $this->providerDecide((int) $id, AttachmentApplication::STATUS_ACCEPTED, AttachmentApplication::STATUS_RECOMMENDED, 'Attachment completed. Their recommendation letter is ready in their portal.');
    }

    /** An organisation with no branches reviews its own students; with branches, the branch admin does. */
    private function providerDecide(int $id, string $from, string $to, string $message): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisations providing attachment can do this.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people');
        }
        $application = AttachmentApplication::findForProvider($id, (int) $this->user['id']);
        if (!$application || !empty($application['branch_id']) || $application['status'] !== $from) {
            flashError('That request could not be updated. Requests for a branch are handled by that branch admin.');
            redirect('/account/people');
        }
        AttachmentApplication::setStatus($id, $to);
        flashSuccess($message);
        redirect('/account/people');
    }
}
