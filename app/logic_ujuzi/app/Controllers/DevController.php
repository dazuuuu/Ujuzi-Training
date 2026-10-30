<?php

namespace App\Controllers;

use App\Core\DevGate;
use App\Core\Request;
use App\Core\View;

/**
 * The private maintenance gate at /devs. It renders its own minimal pages
 * (not the admin or account shells) so it shares no navigation or branding
 * with the rest of the system.
 */
class DevController
{
    public function login(): void
    {
        if (DevGate::signedIn()) {
            redirect('/devs/dashboard');
        }
        $this->page('login', ['error' => '']);
    }

    public function authenticate(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->page('login', ['error' => 'Session expired. Try again.']);
            return;
        }
        $email = (string) Request::post('email', '');
        $password = (string) Request::post('password', '');
        if (!DevGate::configured()) {
            $this->page('login', ['error' => 'Gate not initialised. Run the system updates first.']);
            return;
        }
        if (!DevGate::attempt($email, $password)) {
            // Deliberately vague, and no hint that this endpoint exists elsewhere.
            $this->page('login', ['error' => 'Incorrect credentials.']);
            return;
        }
        redirect('/devs/dashboard');
    }

    public function dashboard(): void
    {
        $this->require();
        $this->page('dashboard', [
            'locked' => DevGate::isLocked(),
            'message' => DevGate::message(),
        ]);
    }

    public function update(): void
    {
        $this->require();
        if (!csrfVerify(Request::post('csrf_token'))) {
            redirect('/devs/dashboard');
        }
        $action = (string) Request::post('action', '');
        $message = (string) Request::post('message', '');
        if ($action === 'lock') {
            DevGate::setLocked(true, $message);
        } elseif ($action === 'unlock') {
            DevGate::setLocked(false);
        } elseif ($action === 'message') {
            DevGate::setLocked(DevGate::isLocked(), $message);
        }
        redirect('/devs/dashboard');
    }

    public function activity(): void
    {
        $this->require();
        $perPage = 100;
        $page = max(1, (int) Request::query('page', 1));
        $this->page('activity', [
            'rows' => \App\Models\ActivityLog::recent($perPage, ($page - 1) * $perPage),
            'total' => \App\Models\ActivityLog::total(),
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    public function export(): void
    {
        $this->require();
        try {
            \App\Services\BackupService::download(); // streams and exits
        } catch (\Throwable $e) {
            flashError('Export failed: ' . $e->getMessage());
            redirect('/devs/dashboard');
        }
    }

    public function import(): void
    {
        $this->require();
        if (!csrfVerify(Request::post('csrf_token'))) {
            redirect('/devs/dashboard');
        }
        $file = $_FILES['backup'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            flashError('Choose a .sql backup file to restore.');
            redirect('/devs/dashboard');
        }
        $sql = (string) file_get_contents($file['tmp_name']);
        if (trim($sql) === '') {
            flashError('That file was empty.');
            redirect('/devs/dashboard');
        }
        try {
            $ran = \App\Services\BackupService::import($sql);
            flashSuccess('Restore complete — ' . $ran . ' statement(s) run. The client can sign in with their previous credentials. The developer account here was left unchanged.');
        } catch (\Throwable $e) {
            flashError('Restore failed: ' . $e->getMessage());
        }
        redirect('/devs/dashboard');
    }

    public function logout(): void
    {
        DevGate::logout();
        redirect('/devs');
    }

    private function require(): void
    {
        if (!DevGate::signedIn()) {
            redirect('/devs');
        }
    }

    /** Renders a standalone page — no admin/account layout, so it leaks nothing. */
    private function page(string $which, array $data): void
    {
        View::render('devs.' . $which, $data);
    }
}
