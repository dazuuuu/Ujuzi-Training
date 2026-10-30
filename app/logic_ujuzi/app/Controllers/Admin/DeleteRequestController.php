<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\DeletionRequest;
use App\Services\Notifier;

/** Delete requests from organisations: who asked, what, and why. Approving deletes it. */
class DeleteRequestController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.delete-requests.index', [
            'pageTitle' => 'Delete requests',
            'activeNav' => 'delete-requests',
            'pending' => DeletionRequest::pending(),
            'recent' => DeletionRequest::recent(),
            'types' => DeletionRequest::types(),
        ]);
    }

    public function decide(string $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/delete-requests');
        }
        $approve = Request::post('decision') === 'approved';
        try {
            $request = DeletionRequest::decide((int) $id, $approve, (int) $this->admin['id']);
        } catch (\Throwable $e) {
            flashError('It could not be deleted (it may still be in use): ' . $e->getMessage());
            redirect('/admin/delete-requests');
        }
        if (!$request) {
            flashError('That request was already handled.');
            redirect('/admin/delete-requests');
        }
        Notifier::toUser((int) $request['requested_by_user_id'],
            $approve ? 'Deleted: ' . $request['label'] : 'Delete request declined: ' . $request['label'],
            $approve ? 'Your delete request was approved' : 'Your delete request was declined',
            $approve ? 'Super Admin approved your request, so "' . $request['label'] . '" has been deleted.' : 'Super Admin declined your request to delete "' . $request['label'] . '", so it stays.',
            'Open your portal', '/account/dashboard');
        flashSuccess($approve ? '"' . $request['label'] . '" deleted.' : 'Request declined — it stays.');
        redirect('/admin/delete-requests');
    }
}
