<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\ChangeRequest;

/** Requests to change locked account details (names, email, ID number). Approving applies them. */
class ChangeRequestController extends BaseAdminController
{
    public function index(): void
    {
        $status = (string) Request::query('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', ''], true)) {
            $status = 'pending';
        }
        try {
            $requests = ChangeRequest::list($status);
        } catch (\Throwable $e) {
            $requests = [];
            flashError('Install the latest updates (Updates in your menu) to use change requests.');
        }
        View::render('admin.change-requests.index', [
            'pageTitle' => 'Detail change requests',
            'activeNav' => 'change-requests',
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function decide(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/change-requests');
        }
        $approve = Request::post('decision') === 'approved';
        $error = ChangeRequest::decide((int) $id, $approve, (int) $this->admin['id'], (string) Request::post('note', ''));
        $error === null
            ? flashSuccess($approve ? 'Approved — the account now has the new details.' : 'Request declined. The person was told.')
            : flashError($error);
        redirect('/admin/change-requests');
    }
}
