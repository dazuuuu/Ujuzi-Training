<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\LoginRoles;
use App\Core\Request;
use App\Core\UserSession;
use App\Core\View;
use App\Models\User;
use App\Services\MailerException;
use App\Services\OtpService;

class PasswordResetController
{
    public function __construct()
    {
        UserSession::start();
    }

    public function showRequest(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $this->renderRequest('', (string) Request::query('role', ''));
    }

    public function send(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }

        $role = (string) Request::post('role', Request::query('role', ''));
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->renderRequest('Your session expired. Please try again.', $role);
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->renderRequest('Enter the email address on your account.', $role, $email);
            return;
        }

        unset($_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_role'], $_SESSION['pending_reset_verified']);

        $user = User::findByIdentifier('email', $email);
        $generic = 'If that email is on an account, we sent a 6-digit code. Check your inbox.';

        if ($user && !empty($user['is_active']) && !empty($user['email'])) {
            try {
                OtpService::issueAndSendForUser((int) $user['id'], $user['email'], 'password_reset');
                $_SESSION['pending_reset_user_id'] = (int) $user['id'];
                $_SESSION['pending_reset_role'] = (string) ($user['role_slug'] ?? $role);
                $_SESSION['pending_reset_verified'] = false;
                flashSuccess($generic);
                redirect('/account/forgot-password/verify');
            } catch (MailerException $e) {
                $this->renderRequest($e->getMessage(), $role, $email);
                return;
            } catch (\Throwable $e) {
                $this->renderRequest('We could not send the code right now. Check Super Admin → Settings SMTP, then try again.', $role, $email);
                return;
            }
        }

        flashSuccess($generic);
        redirect('/account/forgot-password/verify');
    }

    public function showVerify(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $pending = $this->pendingUser();
        View::render('account.reset-verify', [
            'pageTitle' => 'Enter reset code',
            'error' => '',
            'notice' => '',
            'email' => $pending['email'] ?? $this->maskedPendingEmail(),
            'loginPath' => $this->loginPath(),
        ]);
    }

    public function verify(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }

        $pending = $this->pendingUser();
        $error = '';
        $notice = '';

        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif (!$pending) {
            flashError('Start again with the email on your account.');
            redirect('/account/forgot-password');
        } elseif (Request::post('action') === 'resend') {
            try {
                OtpService::issueAndSendForUser((int) $pending['id'], $pending['email'], 'password_reset');
                $notice = 'A new code has been sent to ' . $pending['email'] . '.';
            } catch (MailerException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                $error = 'We could not resend the code right now. Please try again shortly.';
            }
        } else {
            $code = trim((string) Request::post('code', ''));
            if (OtpService::verifyUser((int) $pending['id'], $code, 'password_reset')) {
                $_SESSION['pending_reset_verified'] = true;
                redirect('/account/forgot-password/new');
            }
            $error = 'That code is incorrect or has expired. Please try again or request a new one.';
        }

        View::render('account.reset-verify', [
            'pageTitle' => 'Enter reset code',
            'error' => $error,
            'notice' => $notice,
            'email' => $pending['email'] ?? '',
            'loginPath' => $this->loginPath(),
        ]);
    }

    public function showNew(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        if (!$this->pendingUser() || empty($_SESSION['pending_reset_verified'])) {
            flashError('Verify the email code first.');
            redirect('/account/forgot-password');
        }
        View::render('account.reset-password', [
            'pageTitle' => 'Set a new password',
            'error' => '',
            'loginPath' => $this->loginPath(),
        ]);
    }

    public function storeNew(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }

        $pending = $this->pendingUser();
        if (!$pending || empty($_SESSION['pending_reset_verified'])) {
            flashError('Verify the email code first.');
            redirect('/account/forgot-password');
        }

        if (!csrfVerify(Request::post('csrf_token'))) {
            View::render('account.reset-password', [
                'pageTitle' => 'Set a new password',
                'error' => 'Your session expired. Please try again.',
                'loginPath' => $this->loginPath(),
            ]);
            return;
        }

        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $passwordError = User::passwordError($password, $confirm);
        if ($passwordError) {
            View::render('account.reset-password', [
                'pageTitle' => 'Set a new password',
                'error' => $passwordError,
                'loginPath' => $this->loginPath(),
            ]);
            return;
        }

        User::setPassword((int) $pending['id'], $password);
        User::markEmailVerified((int) $pending['id']);
        $loginPath = $this->loginPath();
        unset($_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_role'], $_SESSION['pending_reset_verified']);
        flashSuccess('Your password was updated. Sign in with your new password.');
        redirect($loginPath);
    }

    private function renderRequest(string $error, string $role, string $email = ''): void
    {
        View::render('account.forgot-password', [
            'pageTitle' => 'Reset password',
            'error' => $error,
            'role' => $role,
            'email' => $email,
            'loginPath' => LoginRoles::loginPath($role),
        ]);
    }

    private function pendingUser(): ?array
    {
        if (empty($_SESSION['pending_reset_user_id'])) {
            return null;
        }
        $user = User::find((int) $_SESSION['pending_reset_user_id']);
        if (!$user || empty($user['is_active'])) {
            unset($_SESSION['pending_reset_user_id'], $_SESSION['pending_reset_role'], $_SESSION['pending_reset_verified']);
            return null;
        }
        return $user;
    }

    private function loginPath(): string
    {
        return LoginRoles::loginPath((string) ($_SESSION['pending_reset_role'] ?? ''));
    }

    private function maskedPendingEmail(): string
    {
        return 'your email';
    }
}
