<?php

namespace App\Controllers\Admin;

use App\Core\AdminSession;
use App\Core\Request;
use App\Core\View;
use App\Models\Admin;
use App\Services\MigrationService;

class AuthController
{
    public function __construct()
    {
        AdminSession::start();
    }

    public function showLogin(): void
    {
        MigrationService::runPending();
        if (Admin::count() === 0) {
            redirect('/setup');
        }
        if (AdminSession::current()) {
            redirect('/admin');
        }
        View::render('admin.login', ['error' => '']);
    }

    public function login(): void
    {
        MigrationService::runPending();
        if (Admin::count() === 0) {
            redirect('/setup');
        }
        if (AdminSession::current()) {
            redirect('/admin');
        }

        $error = '';
        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif (AdminSession::attempt(trim((string) Request::post('email', '')), (string) Request::post('password', ''))) {
            redirect('/admin');
        } else {
            $error = 'Incorrect email or password.';
        }

        View::render('admin.login', ['error' => $error]);
    }

    public function logout(): void
    {
        AdminSession::logout();
        redirect('/admin/login');
    }
}
