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
    }
}
