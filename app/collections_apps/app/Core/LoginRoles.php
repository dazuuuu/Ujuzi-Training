<?php

namespace App\Core;

use App\Models\Role;

class LoginRoles
{
    public static function fromPath(string $path): ?string
    {
        $slug = str_replace('-', '_', strtolower(trim($path)));
        if ($slug === '') {
            return null;
        }
        try {
            $role = Role::findBySlug($slug);
        } catch (\Throwable $e) {
            return null;
        }
        return $role['slug'] ?? null;
    }

    public static function pathSegment(string $slug): string
    {
        return str_replace('_', '-', $slug);
    }

    public static function loginPath(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '/account/login';
        }
        return '/account/login/' . self::pathSegment($slug);
    }

    public static function registerPath(string $slug): ?string
    {
        return match ($slug) {
            'student' => '/account/register',
            'trainer' => '/account/register/trainer',
            'attachment_trainer' => '/account/register/attachment-trainer',
            default => null,
        };
    }

    public static function meta(string $slug, ?array $role = null): array
    {
        $name = $role['name'] ?? roleLabel($slug);
        return match ($slug) {
            'organisation_admin' => [
                'badge' => $name,
                'heading' => 'Organisation admin sign in',
                'blurb' => 'Sign in to your organisation dashboard to manage people, trainer requests, and assigned forms.',
                'need_account' => 'Organisation admins register only through a Super Admin invite URL (valid for 5 minutes, one use).',
            ],
            'attachment_trainer' => [
                'badge' => $name,
                'heading' => 'Attachment trainer sign in',
                'blurb' => 'Sign in to your attachment trainer dashboard. Organisation admins create this account. Students only see you after they finish a course, for the duration on your profile form.',
                'need_account' => 'Ask your organisation admin to create your attachment trainer account.',
            ],
            'trainer' => [
                'badge' => $name,
                'heading' => 'Trainer / tutor / teacher sign in',
                'blurb' => 'Sign in to your trainer dashboard. After you pick organisations on your profile, each organisation must approve you.',
                'need_account' => 'New trainers can register with email and password, then complete the trainer form.',
            ],
            'student' => [
                'badge' => $name,
                'heading' => 'Student sign in',
                'blurb' => 'Sign in to your student dashboard. On first login you complete the form assigned to students.',
                'need_account' => 'New students can register with email and password from the student registration page.',
            ],
            default => [
                'badge' => $name,
                'heading' => $name . ' sign in',
                'blurb' => 'Sign in to the dashboard for this role. Use the email and password saved on your account.',
                'need_account' => 'Ask Super Admin or your organisation admin if you need an account for this role.',
            ],
        };
    }
}
