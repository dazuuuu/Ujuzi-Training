<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\Admin;
use App\Models\Role;

class LmsController
{
    public function home(): void
    {
        $roles = [];
        $needsSetup = false;
        try {
            $roles = Role::all();
            $needsSetup = Admin::count() === 0;
        } catch (\Throwable $e) {
            $needsSetup = true;
        }

        View::render('lms.home', [
            'roles' => $roles,
            'needsSetup' => $needsSetup,
        ]);
    }
}
