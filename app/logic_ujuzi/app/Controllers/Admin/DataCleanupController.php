<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Admin;
use App\Services\DataCleanupService;

class DataCleanupController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.data-cleanup.index', [
            'pageTitle' => 'Data Cleanup',
            'activeNav' => 'data-cleanup',
            'categories' => DataCleanupService::categories(),
            'counts' => DataCleanupService::counts(),
        ]);
    }

    public function destroy(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/data-cleanup');
        }

        $selected = Request::post('categories', []);
        $selected = is_array($selected) ? array_values(array_intersect($selected, array_keys(DataCleanupService::categories()))) : [];
        if (!$selected) {
            flashError('Choose at least one category to delete.');
            redirect('/admin/data-cleanup');
        }

        if (trim((string) Request::post('confirm_phrase', '')) !== 'DELETE') {
            flashError('Type DELETE exactly to confirm.');
            redirect('/admin/data-cleanup');
        }

        $admin = Admin::find((int) $this->admin['id']);
        $password = (string) Request::post('password', '');
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            flashError('Incorrect password. Nothing was deleted.');
            redirect('/admin/data-cleanup');
        }

        try {
            DataCleanupService::wipe($selected);
        } catch (\Throwable $e) {
            flashError('Could not delete that data. Nothing was changed.');
            redirect('/admin/data-cleanup');
        }

        $labels = DataCleanupService::categories();
        $names = array_map(static fn(string $key): string => $labels[$key] ?? $key, $selected);
        flashSuccess('Deleted: ' . implode(', ', $names) . '.');
        redirect('/admin/data-cleanup');
    }

    public function resetEverything(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/data-cleanup');
        }

        try {
            DataCleanupService::wipeEverything();
        } catch (\Throwable $e) {
            flashError('Could not reset the database. Nothing was changed.');
            redirect('/admin/data-cleanup');
        }

        flashSuccess('Complete reset done. Every organisation, user, and their data was deleted. Your admin login and the profile form templates were kept.');
        redirect('/admin/data-cleanup');
    }
}
