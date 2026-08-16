<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\MigrationService;

class UpdateController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.updates.index', [
            'pageTitle' => 'Updates',
            'activeNav' => 'updates',
            'pendingMigrations' => MigrationService::pending(),
        ]);
    }

    public function run(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try the update again.');
            redirect('/admin/updates');
        }

        try {
            $ran = MigrationService::runPending();
            flashSuccess($ran > 0 ? "Updated successfully. Ran {$ran} migration(s)." : 'Everything is already up to date.');
        } catch (\Throwable $e) {
            flashError('Update failed: ' . $e->getMessage());
        }

        redirect('/admin/updates');
    }
}
