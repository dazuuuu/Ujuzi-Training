<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\LoginRoles;
use App\Core\Request;
use App\Core\UserSession;
use App\Core\View;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Services\MailerException;
use App\Services\OtpService;

class AuthController
{
    public function __construct()
    {
        UserSession::start();
    }

    public function choose(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }

        $roles = [];
        try {
            $roles = Role::all();
        } catch (\Throwable $e) {
            $roles = [];
        }

        View::render('account.login-choose', [
            'pageTitle' => 'Choose your login',
            'roles' => $roles,
        ]);
    }

    public function showLogin(string $role): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $this->renderRoleLogin($role, '', 'email', []);
    }

    public function login(string $role): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }

        $resolved = $this->resolvedRole($role);
        if (!$resolved) {
            flashError('Choose a valid role login.');
            redirect('/account/login');
        }

        $method = Request::post('method', 'email');
        $error = '';

        // One box for email or phone, then the password. A phone matches
        // however it is typed (07…, +2547…), including the phone given on the
        // registration form.
        $login = trim((string) Request::post('login', Request::post('email', Request::post('phone', ''))));
        $password = (string) Request::post('password', '');
        $isEmail = str_contains($login, '@');
        $method = $isEmail ? 'email' : 'phone';
        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif ($login === '') {
            $error = 'Enter your email address or phone number.';
        } elseif ($isEmail) {
            $user = User::findByIdentifier('email', strtolower($login));
            $error = $this->authenticateUser($user, $resolved['slug'], $password);
        } else {
            $user = User::findByPhone($login);
            $error = !$user && User::phoneTaken($login)
                ? 'More than one account uses this phone number, so it can\'t be used to sign in yet. Sign in with your email to fix your account.'
                : $this->authenticateUser($user, $resolved['slug'], $password);
        }

        $this->renderRoleLogin($role, $error, $method, [
            'login' => Request::post('login', ''),
        ]);
    }

    public function showVerify(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $pending = $this->pendingUser();
        if (!$pending) {
            redirect($this->pendingLoginPath());
        }
        View::render('account.verify', [
            'error' => '',
            'notice' => '',
            'email' => $pending['email'],
            'loginPath' => $this->pendingLoginPath(),
        ]);
    }

    public function verify(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $pending = $this->pendingUser();
        if (!$pending) {
            redirect($this->pendingLoginPath());
        }

        $error = '';
        $notice = '';
        $loginPath = $this->pendingLoginPath();

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
                $expected = (string) ($_SESSION['pending_login_role'] ?? '');
                if ($expected !== '' && ($pending['role_slug'] ?? '') !== $expected) {
                    unset($_SESSION['pending_user_id'], $_SESSION['pending_login_role']);
                    flashError('Use the login page for ' . roleLabel((string) ($pending['role_slug'] ?? '')) . '.');
                    redirect(LoginRoles::loginPath((string) ($pending['role_slug'] ?? '')));
                }
                User::markEmailVerified((int) $pending['id']);
                unset($_SESSION['pending_user_id'], $_SESSION['pending_login_role']);
                $this->completeLogin($pending);
            }
            $error = 'That code is incorrect or has expired. Please try again or request a new one.';
        }

        View::render('account.verify', [
            'error' => $error,
            'notice' => $notice,
            'email' => $pending['email'],
            'loginPath' => $loginPath,
        ]);
    }

    public function logout(): void
    {
        $user = UserSession::current();
        $path = LoginRoles::loginPath((string) ($user['role_slug'] ?? ''));
        UserSession::logout();
        redirect($path);
    }

    private function authenticateUser(?array $user, string $expectedSlug, string $password): string
    {
        if (!$user) {
            if ($expectedSlug === 'organisation_admin' && str_contains((string) Request::post('login', ''), '@')) {
                try {
                    if (Organisation::count() > 0) {
                        return 'That email is not an organisation admin user account yet. Super Admin must create an admin account for the organisation, or send the organisation admin invite link.';
                    }
                } catch (\Throwable $e) {
                    // Fall through to the generic role login message.
                }
            }
            return !str_contains((string) Request::post('login', ''), '@')
                ? 'We could not find a ' . roleLabel($expectedSlug) . ' account for that phone number.'
                : 'We could not find a ' . roleLabel($expectedSlug) . ' account for that email. Use the login page that matches your role, or register if you are new.';
        }

        if (($user['role_slug'] ?? '') !== $expectedSlug) {
            $actual = (string) ($user['role_slug'] ?? '');
            return 'That account is registered as ' . roleLabel($actual)
                . '. Sign in on the ' . roleLabel($actual) . ' page instead.';
        }

        if (empty($user['is_active'])) {
            $message = ($user['account_status'] ?? '') === 'suspended'
                ? 'This account is suspended. Contact your organisation admin.'
                : 'This account is blocked. Contact your organisation admin.';
            return $message;
        }

        // Email or phone, the password is always checked — on this exact account.
        if (!empty($user['has_password'])) {
            if (User::passwordMatches((int) $user['id'], $password)) {
                $this->completeLogin($user);
            }
            return 'That email / phone or password is incorrect.';
        }
        if (empty($user['email'])) {
            return 'This account has no password yet. Ask your organisation admin to set one.';
        }

        if ($password !== '') {
            return 'This account does not use a password yet. Leave the password blank and we will email a login code, or ask an admin to recreate the account.';
        }

        try {
            OtpService::issueAndSendForUser((int) $user['id'], $user['email'], 'login');
            $_SESSION['pending_user_id'] = (int) $user['id'];
            $_SESSION['pending_login_role'] = $expectedSlug;
            redirect('/account/verify');
        } catch (MailerException $e) {
            return 'We could not send your login code right now. Please try again shortly.';
        } catch (\Throwable $e) {
            return 'Something went wrong. Please try again shortly.';
        }

        return 'Something went wrong. Please try again shortly.';
    }

    private function completeLogin(array $user): void
    {
        User::touchLastLogin((int) $user['id']);
        UserSession::login((int) $user['id']);
        $fresh = UserSession::current() ?: $user;
        if (!empty($fresh['must_change_password'])) {
            flashSuccess('Set your own password to finish signing in.');
            redirect('/account/change-password');
        }
        $home = AccountRedirect::home($fresh);
        if ($home === '/account/profile') {
            flashSuccess('Complete the registration form assigned to your role to finish signing in.');
        }
        redirect($home);
    }

    private function pendingUser(): ?array
    {
        if (empty($_SESSION['pending_user_id'])) {
            return null;
        }
        $user = User::find((int) $_SESSION['pending_user_id']);
        if (!$user) {
            unset($_SESSION['pending_user_id'], $_SESSION['pending_login_role']);
            return null;
        }
        return $user;
    }

    private function pendingLoginPath(): string
    {
        $slug = (string) ($_SESSION['pending_login_role'] ?? '');
        return LoginRoles::loginPath($slug);
    }

    private function resolvedRole(string $role): ?array
    {
        $slug = LoginRoles::fromPath($role);
        if (!$slug) {
            return null;
        }
        return Role::findBySlug($slug);
    }

    private function renderRoleLogin(string $role, string $error, string $method, array $old): void
    {
        $resolved = $this->resolvedRole($role);
        if (!$resolved) {
            flashError('Choose a valid role login.');
            redirect('/account/login');
        }

        $slug = $resolved['slug'];
        View::render('account.login', [
            'pageTitle' => $resolved['name'] . ' sign in',
            'error' => $error,
            'method' => $method,
            'old' => $old,
            'roleSlug' => $slug,
            'role' => $resolved,
            'roleMeta' => LoginRoles::meta($slug, $resolved),
            'loginPath' => LoginRoles::loginPath($slug),
            'registerPath' => LoginRoles::registerPath($slug),
        ]);
    }
}
