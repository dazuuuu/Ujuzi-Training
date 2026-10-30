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

        // Record what the owner (and any admin) does, for the developer console.
        \App\Models\ActivityLog::record($this->admin, $_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
    }
}
