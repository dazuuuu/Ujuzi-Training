<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\User;

class ChangePasswordController extends BaseAccountController
{
    public function show(): void
    {
        $this->render('account.change-password', [
            'pageTitle' => 'Set your password',
            'forced' => !empty($this->user['must_change_password']),
        ]);
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/change-password');
        }

        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');
        $error = User::passwordError($password, $confirm);
        if ($error) {
            flashError($error);
            redirect('/account/change-password');
        }

        User::completeForcedPasswordChange((int) $this->user['id'], $password);
        flashSuccess('Your password is set. Use it next time you sign in.');
        redirect('/account/dashboard');
    }
}
