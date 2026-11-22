<?php

namespace App\Controllers\Admin;

use App\Core\AdminSession;

abstract class BaseAdminController
{
    protected array $admin;

    public function __construct()
    {
        AdminSession::start();
        $this->admin = AdminSession::require();

        // Limited admins only reach the sections an owner gave them.
        $path = \App\Core\Url::currentPath();
        $section = \App\Core\AdminAccess::sectionForPath($path);
        if (!\App\Core\AdminAccess::allows($this->admin, $section)) {
            flashError($section === 'admins'
                ? 'Only a main Super Admin can manage admins.'
                : 'Your admin account does not have access to that section. Ask a main Super Admin.');
            redirect('/admin');
        }

        // Deleting data always needs the admin's own password — except the
        // System Account, which has no row/password to confirm against; it
        // already proved itself at its own (hardcoded) sign-in.
        $isSystemAccount = \App\Core\SystemAccount::isId((int) ($this->admin['id'] ?? -1));
        if (!$isSystemAccount && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $this->deletesData($path)) {
            $row = \App\Models\Admin::find((int) ($this->admin['id'] ?? 0));
            $given = (string) \App\Core\Request::post('confirm_password', '');
            if (!$row || $given === '' || !password_verify($given, (string) ($row['password_hash'] ?? ''))) {
                flashError('Wrong or missing password. Nothing was deleted.');
                redirect('/' . implode('/', array_slice(explode('/', trim($path, '/')), 0, 2)));
            }
        }

        // Record what the owner (and any admin) does, for the developer console.
        \App\Models\ActivityLog::record($this->admin, $_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
    }

    private function deletesData(string $path): bool
    {
        if (preg_match('#/(delete|data-cleanup|reset-everything|clear)(/|$)#', $path)) {
            return true;
        }
        return preg_match('#^/admin/delete-requests/\d+$#', $path) === 1
            && (string) \App\Core\Request::post('decision', '') === 'approved';
    }
}
