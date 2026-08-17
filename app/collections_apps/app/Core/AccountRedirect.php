<?php

namespace App\Core;

use App\Models\Form;
use App\Models\FormResponse;

class AccountRedirect
{
    public static function home(array $user): string
    {
        return self::needsProfile($user) ? '/account/profile' : '/account/dashboard';
    }

    public static function needsProfile(array $user): bool
    {
        $forms = Form::forRole((int) $user['role_id'], true);
        if (!$forms) {
            return false;
        }
        foreach ($forms as $form) {
            $response = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
            if (!$response || empty($response['submitted_at'])) {
                return true;
            }
        }
        return false;
    }
}
