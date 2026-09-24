<?php

namespace App\Controllers;

use App\Core\AdminSession;
use App\Core\View;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Role;
use App\Services\MigrationService;

class LmsController
{
    public function home(): void
    {
        $roles = [];
        $needsSetup = false;
        $pendingMigrations = [];
        $isSuperAdmin = false;
        try {
            $roles = Role::all();
            $needsSetup = Admin::count() === 0;
        } catch (\Throwable $e) {
            $needsSetup = true;
        }

        try {
            AdminSession::start();
            $isSuperAdmin = AdminSession::current() !== null;
            $pendingMigrations = MigrationService::pending();
        } catch (\Throwable $e) {
            $pendingMigrations = [];
        }

        View::render('lms.home', [
            'roles' => $roles,
            'needsSetup' => $needsSetup,
            'pendingMigrations' => $pendingMigrations,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function courses(): void
    {
        try {
            $courses = Course::publicListing();
        } catch (\Throwable $e) {
            $courses = [];
        }

        View::render('lms.courses', [
            'courses' => $courses,
        ]);
    }
}
