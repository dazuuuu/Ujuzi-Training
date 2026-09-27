<?php

namespace App\Core;

use App\Models\Form;
use App\Models\FormResponse;

class AccountRedirect
{
    public static function home(array $user): string
    {
        // Students aren't forced into the full registration form right after
        // signing up — CourseController::requireFullRegistration() (which
        // still calls needsProfile()) asks for it only when they enroll in a
        // course that needs it.
        if (($user['role_slug'] ?? '') === 'student') {
            return '/account/dashboard';
        }
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
