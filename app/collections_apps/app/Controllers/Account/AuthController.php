<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Core\UserSession;
use App\Core\View;
use App\Models\User;
use App\Services\MailerException;
use App\Services\OtpService;

class AuthController
{
    public function __construct()
    {
        UserSession::start();
    }

    public function showLogin(): void
    {
        if (UserSession::current()) {
            redirect('/account/dashboard');
        }
        View::render('account.login', ['error' => '', 'method' => 'email', 'old' => []]);
    }

    public function login(): void
    {
        if (UserSession::current()) {
            redirect('/account/dashboard');
        }

        $method = Request::post('method', 'email');
        $error = '';

        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif ($method === 'phone') {
            $phone = User::normalizePhone((string) Request::post('phone', ''));
            $user = $phone ? User::findByIdentifier('phone', $phone) : null;
            if ($user && !empty($user['is_active'])) {
                UserSession::login((int) $user['id']);
                redirect('/account/dashboard');
            }
            $error = $user && empty($user['is_active'])
                ? 'This account is inactive. Contact your organisation admin.'
                : 'We could not find a user for that phone number. An admin must create your account first.';
        } else {
            $email = strtolower(trim((string) Request::post('email', '')));
            $user = $email ? User::findByIdentifier('email', $email) : null;
            if ($user && !empty($user['is_active'])) {
                try {
                    OtpService::issueAndSendForUser((int) $user['id'], $user['email'], 'login');
                    $_SESSION['pending_user_id'] = (int) $user['id'];
                    redirect('/account/verify');
                } catch (MailerException $e) {
                    $error = 'We could not send your login code right now. Please try again shortly.';
                } catch (\Throwable $e) {
                    $error = 'Something went wrong. Please try again shortly.';
                }
            } elseif ($user && empty($user['is_active'])) {
                $error = 'This account is inactive. Contact your organisation admin.';
            } else {
                $error = 'We could not find a user for that email. An admin must create your account first.';
            }
        }

        View::render('account.login', [
            'error' => $error,
            'method' => $method,
            'old' => ['email' => Request::post('email', ''), 'phone' => Request::post('phone', '')],
        ]);
    }

    public function showVerify(): void
    {
        if (UserSession::current()) {
            redirect('/account/dashboard');
        }
        $pending = $this->pendingUser();
        if (!$pending) {
            redirect('/account/login');
        }
        View::render('account.verify', ['error' => '', 'notice' => '', 'email' => $pending['email']]);
    }

    public function verify(): void
    {
        if (UserSession::current()) {
            redirect('/account/dashboard');
        }
        $pending = $this->pendingUser();
        if (!$pending) {
            redirect('/account/login');
        }

        $error = '';
        $notice = '';

        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif (Request::post('action') === 'resend') {
            try {
                OtpService::issueAndSendForUser((int) $pending['id'], $pending['email'], 'login');
                $notice = 'A new code has been sent to ' . $pending['email'] . '.';
            } catch (MailerException $e) {
                $error = 'We could not resend the code right now. Please try again shortly.';
            } catch (\Throwable $e) {
                $error = 'Something went wrong. Please try again shortly.';
            }
        } else {
            $code = trim((string) Request::post('code', ''));
            if (OtpService::verifyUser((int) $pending['id'], $code, 'login')) {
                User::markEmailVerified((int) $pending['id']);
                UserSession::login((int) $pending['id']);
                unset($_SESSION['pending_user_id']);
                redirect('/account/dashboard');
            }
            $error = 'That code is incorrect or has expired. Please try again or request a new one.';
        }

        View::render('account.verify', ['error' => $error, 'notice' => $notice, 'email' => $pending['email']]);
    }

    public function logout(): void
    {
        UserSession::logout();
        redirect('/account/login');
    }

    private function pendingUser(): ?array
    {
        if (empty($_SESSION['pending_user_id'])) {
            return null;
        }
        $user = User::find((int) $_SESSION['pending_user_id']);
        if (!$user) {
            unset($_SESSION['pending_user_id']);
            return null;
        }
        return $user;
    }
}
