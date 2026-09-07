<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Database;
use App\Core\Request;
use App\Core\UserSession;
use App\Core\View;
use App\Models\OrganisationAdminInvite;
use App\Models\Role;
use App\Models\User;

class RegisterController
{
    public function __construct()
    {
        UserSession::start();
    }

    public function showStudent(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $this->renderStudent('');
    }

    public function storeStudent(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->renderStudent('Your session expired. Please try again.');
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $error = $this->validateCredentials($email, $password, $confirm);
        if ($error) {
            $this->renderStudent($error, $email);
            return;
        }

        $role = Role::findBySlug('student');
        if (!$role) {
            $this->renderStudent('Student registration is not available yet. Ask Super Admin to finish setup.');
            return;
        }

        try {
            $userId = User::create([
                'role_id' => (int) $role['id'],
                'organisation_id' => null,
                'email' => $email,
                'phone' => '',
                'first_name' => '',
                'last_name' => '',
                'password' => $password,
                'is_active' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->renderStudent('That email is already registered. Sign in instead.', $email);
                return;
            }
            throw $e;
        }

        $this->loginAndLand($userId, 'Welcome. Complete the student registration form assigned to your role.');
    }

    public function showTrainer(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $this->renderTrainer('');
    }

    public function storeTrainer(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->renderTrainer('Your session expired. Please try again.');
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $error = $this->validateCredentials($email, $password, $confirm);
        if ($error) {
            $this->renderTrainer($error, $email);
            return;
        }

        $role = Role::findBySlug('trainer');
        if (!$role) {
            $this->renderTrainer('Trainer registration is not available yet. Ask Super Admin to finish setup.');
            return;
        }

        try {
            $userId = User::create([
                'role_id' => (int) $role['id'],
                'organisation_id' => null,
                'email' => $email,
                'phone' => '',
                'first_name' => '',
                'last_name' => '',
                'password' => $password,
                'is_active' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->renderTrainer('That email is already registered. Sign in instead.', $email);
                return;
            }
            throw $e;
        }

        $this->loginAndLand($userId, 'Welcome. Complete the trainer registration form and pick the organisation(s) you want to teach for.');
    }

    public function showAttachmentTrainer(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $this->renderAttachmentTrainer('');
    }

    public function storeAttachmentTrainer(): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->renderAttachmentTrainer('Your session expired. Please try again.');
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $error = $this->validateCredentials($email, $password, $confirm);
        if ($error) {
            $this->renderAttachmentTrainer($error, $email);
            return;
        }

        $role = Role::findBySlug('attachment_trainer');
        if (!$role) {
            $this->renderAttachmentTrainer('Attachment trainer registration is not available yet. Ask Super Admin to finish setup.');
            return;
        }

        try {
            $userId = User::create([
                'role_id' => (int) $role['id'],
                'organisation_id' => null,
                'email' => $email,
                'phone' => '',
                'first_name' => '',
                'last_name' => '',
                'password' => $password,
                'is_active' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->renderAttachmentTrainer('That email is already registered. Sign in instead.', $email);
                return;
            }
            throw $e;
        }

        $this->loginAndLand($userId, 'Welcome. Complete the attachment trainer registration form assigned to your role.');
    }

    public function showOrganisationAdmin(string $token): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        $invite = $this->usableInvite($token);
        if (is_string($invite)) {
            $this->renderBroken($invite);
            return;
        }
        $this->renderOrgAdmin($invite, $token, '');
    }

    public function storeOrganisationAdmin(string $token): void
    {
        if (UserSession::current()) {
            redirect(AccountRedirect::home(UserSession::current()));
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            $invite = OrganisationAdminInvite::findValidByToken($token);
            if (!$invite) {
                $this->renderBroken('This registration link is not valid.');
                return;
            }
            $this->renderOrgAdmin($invite, $token, 'Your session expired. Please try again.');
            return;
        }

        $invite = $this->usableInvite($token);
        if (is_string($invite)) {
            $this->renderBroken($invite);
            return;
        }

        $email = strtolower(trim((string) Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirmation', '');
        $error = $this->validateCredentials($email, $password, $confirm);
        if ($error) {
            $this->renderOrgAdmin($invite, $token, $error, $email);
            return;
        }

        $role = Role::findBySlug('organisation_admin');
        if (!$role) {
            $this->renderOrgAdmin($invite, $token, 'Organisation admin registration is not available yet.');
            return;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $locked = OrganisationAdminInvite::findValidByToken($token);
            if (!$locked || !empty($locked['is_used']) || !empty($locked['is_expired']) || empty($locked['organisation_is_active'])) {
                $pdo->rollBack();
                $this->renderBroken($this->inviteFailureMessage($locked));
                return;
            }

            $userId = User::create([
                'role_id' => (int) $role['id'],
                'organisation_id' => (int) $locked['organisation_id'],
                'email' => $email,
                'phone' => '',
                'first_name' => '',
                'last_name' => '',
                'password' => $password,
                'is_active' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]);

            if (!OrganisationAdminInvite::markUsed((int) $locked['id'], $userId)) {
                $pdo->rollBack();
                $this->renderBroken('This registration link has already been used. Ask Super Admin for a new one.');
                return;
            }

            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ((string) $e->getCode() === '23000') {
                $this->renderOrgAdmin($invite, $token, 'That email is already registered. Sign in instead.', $email);
                return;
            }
            $this->renderOrgAdmin($invite, $token, 'Could not create the account. Please try again.', $email);
            return;
        }

        $this->loginAndLand($userId, 'Welcome. Complete the organisation admin form assigned to your role.');
    }

    private function usableInvite(string $token): array|string
    {
        $invite = OrganisationAdminInvite::findValidByToken($token);
        if (!$invite) {
            return 'This registration link is not valid.';
        }
        return $this->inviteFailureMessage($invite) ?: $invite;
    }

    private function inviteFailureMessage(?array $invite): string
    {
        if (!$invite) {
            return 'This registration link is not valid.';
        }
        if (empty($invite['organisation_is_active'])) {
            return 'This organisation is not accepting registrations.';
        }
        if (!empty($invite['is_used'])) {
            return 'This registration link has already been used. Only Super Admin can create a new secure URL.';
        }
        if (!empty($invite['is_expired'])) {
            return 'This registration link expired after 5 minutes. Ask Super Admin to generate a new secure URL.';
        }
        return '';
    }

    private function validateCredentials(string $email, string $password, string $confirm): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        $existing = User::findByIdentifier('email', $email);
        if ($existing) {
            return 'That email is already registered. Sign in instead.';
        }
        return User::passwordError($password, $confirm) ?? '';
    }

    private function loginAndLand(int $userId, string $message): void
    {
        User::touchLastLogin($userId);
        UserSession::login($userId);
        $user = UserSession::current();
        flashSuccess($message);
        redirect($user ? AccountRedirect::home($user) : '/account/dashboard');
    }

    private function renderStudent(string $error, string $email = ''): void
    {
        View::render('account.register', [
            'pageTitle' => 'Student registration',
            'error' => $error,
            'email' => $email,
            'mode' => 'student',
            'action' => url('/account/register'),
            'heading' => 'Create a student account',
            'blurb' => 'Register with email and password. After you sign in, the first page you see is the registration form Super Admin assigned to students.',
            'loginUrl' => '/account/login/student',
        ]);
    }

    private function renderTrainer(string $error, string $email = ''): void
    {
        View::render('account.register', [
            'pageTitle' => 'Trainer registration',
            'error' => $error,
            'email' => $email,
            'mode' => 'trainer',
            'action' => url('/account/register/trainer'),
            'heading' => 'Create a trainer account',
            'blurb' => 'Register as a trainer, tutor, or teacher. After you sign in, fill the assigned form and pick organisation(s). Each organisation must approve you before you appear on their dashboard.',
            'loginUrl' => '/account/login/trainer',
        ]);
    }

    private function renderAttachmentTrainer(string $error, string $email = ''): void
    {
        View::render('account.register', [
            'pageTitle' => 'Attachment trainer registration',
            'error' => $error,
            'email' => $email,
            'mode' => 'attachment_trainer',
            'action' => url('/account/register/attachment-trainer'),
            'heading' => 'Create an attachment trainer account',
            'blurb' => 'Register with email and password. After you sign in, you will complete the profile form assigned specifically to attachment trainers.',
            'loginUrl' => '/account/login/attachment-trainer',
        ]);
    }

    private function renderOrgAdmin(array $invite, string $token, string $error, string $email = ''): void
    {
        View::render('account.register', [
            'pageTitle' => 'Organisation admin registration',
            'error' => $error,
            'email' => $email,
            'mode' => 'organisation_admin',
            'action' => url('/register/organisation-admin/' . $token),
            'heading' => 'Register as organisation admin',
            'blurb' => 'Create an account for ' . ($invite['organisation_name'] ?? 'this organisation') . ' with email and password. This link expires in 5 minutes and can only be used once.',
            'organisationName' => $invite['organisation_name'] ?? '',
            'expiresAt' => $invite['expires_at'] ?? '',
            'loginUrl' => '/account/login/organisation-admin',
        ]);
    }

    private function renderBroken(string $message): void
    {
        View::render('account.register-expired', [
            'pageTitle' => 'Registration link unavailable',
            'message' => $message,
        ]);
    }
}
