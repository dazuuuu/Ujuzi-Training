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
            // Saving one field (e.g. just the ID number) must not count as finishing the form:
            // every required field needs an answer.
            $answers = is_array($response['answers'] ?? null) ? $response['answers'] : (json_decode((string) ($response['answers'] ?? ''), true) ?: []);
            try {
                $fields = \App\Models\FormField::forForm((int) $form['id']);
            } catch (\Throwable $e) {
                $fields = [];
            }
            foreach ($fields as $field) {
                if (!empty($field['is_required']) && self::blank($answers[$field['field_key']] ?? null)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function blank($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $part) {
                if (!self::blank($part)) {
                    return false;
                }
            }
            return true;
        }
        return $value === null || trim((string) $value) === '';
    }
}
