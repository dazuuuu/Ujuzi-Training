<?php

namespace App\Controllers\Account;

use App\Core\View;
use App\Models\User;

/**
 * The Verify button in the email. Works without signing in: it confirms the
 * new email, unlocks the account, then the page closes itself.
 */
class EmailVerificationController
{
    public function verify(string $token): void
    {
        $user = preg_match('/^[a-f0-9]{48}$/', $token) ? User::confirmEmailChange($token) : null;
        View::render('account.verify-email', ['ok' => $user !== null, 'email' => $user['email'] ?? '']);
    }
}
