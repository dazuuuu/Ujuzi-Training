<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\User;
use App\Services\MailerException;
use App\Services\OtpService;

/**
 * Changing a password needs a one-time code sent to the account's email.
 * The first password after an admin-assigned default is set without one,
 * since that sign-in just proved the default.
 */
class ChangePasswordController extends BaseAccountController
{
    private const PURPOSE = 'password_change';

    public function show(): void
    {
        $forced = !empty($this->user['must_change_password']);
        $sentAt = (int) ($_SESSION['password_change_code_at'] ?? 0);
        $this->render('account.change-password', [
            'pageTitle' => $forced ? 'Set your password' : 'Change password',
            'forced' => $forced,
            'hasEmail' => !empty($this->user['email']),
            'codeSent' => $sentAt > time() - 600,
            'email' => (string) ($this->user['email'] ?? ''),
        ]);
    }

    public function sendCode(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/change-password');
        }
        if (empty($this->user['email'])) {
            flashError('Add an email address on your profile first — the code is sent there.');
            redirect('/account/change-password');
        }
        $last = (int) ($_SESSION['password_change_code_at'] ?? 0);
        if ($last > time() - 30) {
            flashError('A code was just sent. Wait a few seconds before asking for another.');
            redirect('/account/change-password');
        }
        try {
            OtpService::issueAndSendForUser((int) $this->user['id'], (string) $this->user['email'], self::PURPOSE);
        } catch (MailerException $e) {
            flashError('We could not send the code: ' . $e->getMessage());
            redirect('/account/change-password');
        }
        $_SESSION['password_change_code_at'] = time();
        flashSuccess('We sent a 6-digit code to ' . $this->user['email'] . '. It expires in 10 minutes.');
        redirect('/account/change-password');
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/change-password');
        }

        $forced = !empty($this->user['must_change_password']);
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');
        $error = User::passwordError($password, $confirm);
        if ($error) {
            flashError($error);
            redirect('/account/change-password');
        }

        if (!$forced) {
            $code = preg_replace('/\D/', '', (string) Request::post('code', ''));
            if ($code === '' || !OtpService::verifyUser((int) $this->user['id'], $code, self::PURPOSE)) {
                flashError('That code is wrong or has expired. Check your email, or send a new code.');
                redirect('/account/change-password');
            }
            unset($_SESSION['password_change_code_at']);
        }

        User::completeForcedPasswordChange((int) $this->user['id'], $password);
        flashSuccess($forced ? 'Your password is set. Use it next time you sign in.' : 'Your password was changed.');
        redirect('/account/dashboard');
    }
}
