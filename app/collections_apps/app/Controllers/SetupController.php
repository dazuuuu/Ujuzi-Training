<?php

namespace App\Controllers;

use App\Core\AdminSession;
use App\Core\Request;
use App\Core\View;
use App\Models\Admin;
use App\Models\StoreSetting;
use App\Services\MigrationService;
use App\Services\UploadException;
use App\Services\UploadService;

class SetupController
{
    public function __construct()
    {
        AdminSession::start();
    }

    public function index(): void
    {
        $ran = MigrationService::runPending();
        if (Admin::count() > 0) {
            redirect('/admin/login');
        }

        View::render('setup.index', [
            'errors' => [],
            'old' => ['email' => '', 'name' => ''],
            'ranMigrations' => $ran,
        ]);
    }

    public function store(): void
    {
        $ran = MigrationService::runPending();
        if (Admin::count() > 0) {
            redirect('/admin/login');
        }

        $errors = [];
        if (!csrfVerify(Request::post('csrf_token'))) {
            $errors[] = 'Your session expired. Please resubmit the setup form.';
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $name = trim((string) Request::post('name', ''));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid admin email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password confirmation does not match.';
        }

        $logoPath = null;
        $file = Request::file('store_logo');
        if (!$errors && $file && !empty($file['name'])) {
            try {
                $logoPath = UploadService::store($file, 'settings');
            } catch (UploadException $e) {
                $errors[] = 'Logo upload: ' . $e->getMessage();
            }
        }

        if ($errors) {
            View::render('setup.index', [
                'errors' => $errors,
                'old' => ['email' => $email, 'name' => $name],
                'ranMigrations' => $ran,
            ]);
            return;
        }

        $adminId = Admin::create($email, $password);
        if ($logoPath) {
            StoreSetting::set('store_logo', $logoPath);
        }

        AdminSession::start();
        AdminSession::loginAdmin($adminId, $email);
        redirect('/admin');
    }
}
