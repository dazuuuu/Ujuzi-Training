<?php

namespace App\Controllers\Admin;

use App\Core\AdminSession;
use App\Core\Request;
use App\Core\View;
use App\Models\Admin;
use App\Services\MailerException;
use App\Services\MailerService;

class AuthController
{
    public function __construct()
    {
        AdminSession::start();
    }

    public function showLogin(): void
    {
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

    public function showForgot(): void
    {
        $this->guardGuest();
        View::render('admin.forgot-password', ['error' => '', 'email' => '']);
    }

    public function sendReset(): void
    {
        $this->guardGuest();
        if (!csrfVerify(Request::post('csrf_token'))) {
            View::render('admin.forgot-password', ['error' => 'Your session expired. Please try again.', 'email' => '']);
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $generic = 'If that email is a Super Admin account, we sent a 6-digit code.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            View::render('admin.forgot-password', ['error' => 'Enter your Super Admin email.', 'email' => $email]);
            return;
        }

        unset($_SESSION['admin_reset']);

        $admin = Admin::findByEmail($email);
        if ($admin) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            try {
                MailerService::sendOtp($admin['email'], $code, 'password_reset');
                $_SESSION['admin_reset'] = [
                    'admin_id' => (int) $admin['id'],
                    'email' => $admin['email'],
                    'hash' => password_hash($code, PASSWORD_DEFAULT),
                    'expires' => time() + 600,
                    'verified' => false,
                ];
                flashSuccess($generic);
                redirect('/admin/forgot-password/verify');
            } catch (MailerException $e) {
                View::render('admin.forgot-password', ['error' => $e->getMessage(), 'email' => $email]);
                return;
            }
        }

        flashSuccess($generic);
        redirect('/admin/forgot-password/verify');
    }

    public function showResetVerify(): void
    {
        $this->guardGuest();
        $reset = $this->pendingReset();
        View::render('admin.reset-verify', [
            'error' => '',
            'notice' => '',
            'email' => $reset['email'] ?? 'your email',
        ]);
    }

    public function verifyReset(): void
    {
        $this->guardGuest();
        $reset = $this->pendingReset();
        if (!$reset) {
            flashError('Start again with your Super Admin email.');
            redirect('/admin/forgot-password');
        }

        if (!csrfVerify(Request::post('csrf_token'))) {
            View::render('admin.reset-verify', ['error' => 'Your session expired. Please try again.', 'notice' => '', 'email' => $reset['email']]);
            return;
        }

        if (Request::post('action') === 'resend') {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            try {
                MailerService::sendOtp($reset['email'], $code, 'password_reset');
                $_SESSION['admin_reset']['hash'] = password_hash($code, PASSWORD_DEFAULT);
                $_SESSION['admin_reset']['expires'] = time() + 600;
                View::render('admin.reset-verify', [
                    'error' => '',
                    'notice' => 'A new code has been sent to ' . $reset['email'] . '.',
                    'email' => $reset['email'],
                ]);
            } catch (MailerException $e) {
                View::render('admin.reset-verify', ['error' => $e->getMessage(), 'notice' => '', 'email' => $reset['email']]);
            }
            return;
        }

        $code = trim((string) Request::post('code', ''));
        if (empty($reset['hash']) || time() > (int) $reset['expires'] || !password_verify($code, $reset['hash'])) {
            View::render('admin.reset-verify', [
                'error' => 'That code is incorrect or has expired.',
                'notice' => '',
                'email' => $reset['email'],
            ]);
            return;
        }

        $_SESSION['admin_reset']['verified'] = true;
        $_SESSION['admin_reset']['expires'] = time() + 600;
        redirect('/admin/forgot-password/new');
    }

    public function showNewPassword(): void
    {
        $this->guardGuest();
        $reset = $this->pendingReset();
        if (!$reset || empty($reset['verified'])) {
            flashError('Verify the email code first.');
            redirect('/admin/forgot-password');
        }
        View::render('admin.reset-password', ['error' => '']);
    }

    public function storeNewPassword(): void
    {
        $this->guardGuest();
        $reset = $this->pendingReset();
        if (!$reset || empty($reset['verified'])) {
            flashError('Verify the email code first.');
            redirect('/admin/forgot-password');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            View::render('admin.reset-password', ['error' => 'Your session expired. Please try again.']);
            return;
        }

        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        if (strlen($password) < 8) {
            View::render('admin.reset-password', ['error' => 'Password must be at least 8 characters.']);
            return;
        }
        if ($password !== $confirm) {
            View::render('admin.reset-password', ['error' => 'Password confirmation does not match.']);
            return;
        }

        Admin::setPassword((int) $reset['admin_id'], $password);
        unset($_SESSION['admin_reset']);
        flashSuccess('Your Super Admin password was updated. Sign in with the new password.');
        redirect('/admin/login');
    }

    public function logout(): void
    {
        AdminSession::logout();
        redirect('/admin/login');
    }

    private function guardGuest(): void
    {
        if (Admin::count() === 0) {
            redirect('/setup');
        }
        if (AdminSession::current()) {
            redirect('/admin');
        }
    }

    private function pendingReset(): ?array
    {
        $reset = $_SESSION['admin_reset'] ?? null;
        if (!is_array($reset) || empty($reset['admin_id'])) {
            return null;
        }
        if (time() > (int) ($reset['expires'] ?? 0) && empty($reset['verified'])) {
            unset($_SESSION['admin_reset']);
            return null;
        }
        return $reset;
    }
}
