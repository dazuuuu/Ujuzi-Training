<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\User;
use App\Services\MailerService;

/**
 * Accounts locked because they shared an email or phone with another
 * account: the owner gives their own email (and phone), gets an email with a
 * Verify button, and the account unlocks once it is clicked. The same
 * verification confirms an email changed from the profile.
 */
class AccountLockController extends BaseAccountController
{
    public function show(): void
    {
        if (!User::isLocked($this->user)) {
            redirect('/account/dashboard');
        }
        $this->render('account.locked', [
            'pageTitle' => 'Fix your account',
            'activeNav' => '',
            'mailReady' => MailerService::isConfigured(),
        ]);
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/locked');
        }
        $id = (int) $this->user['id'];
        $email = strtolower(trim((string) Request::post('email', '')));
        $phone = trim((string) Request::post('phone', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flashError('Enter a valid email address you can open.');
            redirect('/account/locked');
        }
        if (User::emailTaken($email, $id)) {
            flashError('That email is already used by another account. Use an email that is only yours.');
            redirect('/account/locked');
        }
        if ($phone !== '' && (strlen(preg_replace('/\D/', '', $phone)) < 9 || User::phoneTaken($phone, $id))) {
            flashError(strlen(preg_replace('/\D/', '', $phone)) < 9 ? 'Enter a full phone number.' : 'That phone number is already used by another account. Use a number that is only yours.');
            redirect('/account/locked');
        }
        if (str_contains((string) ($this->user['lock_reason'] ?? ''), 'phone') && $phone === '') {
            flashError('Another account uses your phone number, so enter a phone number that is only yours.');
            redirect('/account/locked');
        }
        $error = self::sendVerification($id, $email, $phone !== '' ? $phone : null, userDisplayName($this->user));
        if ($error !== null) {
            flashError($error . ' Ask Super Admin to unlock your account.');
            redirect('/account/locked');
        }
        flashSuccess('We sent a verification link to ' . $email . '. Open it and press "Verify my email" — your account unlocks straight away.');
        redirect('/account/locked');
    }

    /** Sends the email with the Verify button. Returns null when sent, else why not. */
    public static function sendVerification(int $userId, string $email, ?string $phone, string $name): ?string
    {
        if (!MailerService::isConfigured()) {
            return 'Email is not set up on this system yet, so the verification link cannot be sent.';
        }
        $token = User::startEmailChange($userId, $email, $phone);
        try {
            MailerService::sendNotice(
                $email,
                'Verify your email for ' . appName(),
                'Verify your email',
                'Hello ' . $name . ",\n\nPress the button to confirm this is your email address. Your account will be updated (and unlocked if it was locked) straight away.\n\nThe link works for 2 days.",
                'Verify my email',
                Url::absolute('/account/verify-email/' . $token)
            );
        } catch (\Throwable $e) {
            return 'The verification email could not be sent.';
        }
        return null;
    }
}
