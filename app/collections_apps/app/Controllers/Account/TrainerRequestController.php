<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\OrganisationMembership;

class TrainerRequestController extends BaseAccountController
{
    public function approve(string $id): void
    {
        $this->decide((int) $id, true);
    }

    public function reject(string $id): void
    {
        $this->decide((int) $id, false);
    }

    private function decide(int $id, bool $approve): void
    {
        if (($this->user['role_slug'] ?? '') !== 'organisation_admin' || empty($this->user['organisation_id'])) {
            flashError('Only organisation admins can approve trainers.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/dashboard');
        }

        $orgId = (int) $this->user['organisation_id'];
        $ok = $approve
            ? OrganisationMembership::approve($id, $orgId, (int) $this->user['id'])
            : OrganisationMembership::reject($id, $orgId, (int) $this->user['id']);

        if (!$ok) {
            flashError('That trainer request could not be updated.');
            redirect('/account/dashboard');
        }

        flashSuccess($approve
            ? 'Trainer approved. They are now assigned to your organisation.'
            : 'Trainer request rejected.');
        redirect('/account/dashboard');
    }
}
