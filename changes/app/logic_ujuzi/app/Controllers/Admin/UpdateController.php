<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\MigrationService;

class UpdateController extends BaseAdminController
{
    public function index(): void
    {
        $status = MigrationService::status();
        $pending = array_values(array_map(
            fn(array $row): string => $row['name'],
            array_filter($status, fn(array $row): bool => !$row['applied'])
        ));

        View::render('admin.updates.index', [
            'pageTitle' => 'Updates',
            'activeNav' => 'updates',
            'pendingMigrations' => $pending,
            'migrationStatus' => $status,
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
            $stillPending = MigrationService::pending();
            if ($stillPending) {
                flashSuccess($ran > 0 ? "Ran {$ran} migration(s). Some updates are still pending." : 'Some updates are still pending.');
                redirect('/admin/updates');
            }
            flashSuccess($ran > 0 ? "Updated successfully. Ran {$ran} migration(s)." : 'Everything is already up to date.');
        } catch (\Throwable $e) {
            flashError('Update failed: ' . $e->getMessage());
            redirect('/admin/updates');
        }

        redirect('/admin');
    }
}
