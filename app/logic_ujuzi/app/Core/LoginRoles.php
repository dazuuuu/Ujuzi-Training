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
                'heading' => 'Attachment provider sign in',
                'blurb' => 'Sign in to register your organisation, manage branches, and ask organisations providing courses to take their students. Their students see you once they approve you.',
                'need_account' => 'Register as an attachment provider, or ask Super Admin to create your account.',
            ],
            'trainer' => [
                'badge' => $name,
                'heading' => 'Tutor sign in',
                'blurb' => 'Sign in to your tutor dashboard to create courses under your organisation\'s categories.',
                'need_account' => 'Tutors are registered by their organisation. Ask your organisation for your sign-in details.',
            ],
            'student' => [
                'badge' => $name,
                'heading' => 'Student sign in',
                'blurb' => 'Sign in to your student dashboard. On first login you complete the form assigned to students.',
                'need_account' => 'New students can register with email and password from the student registration page.',
            ],
            'branch_admin' => [
                'badge' => $name,
                'heading' => 'Attachment branch admin sign in',
                'blurb' => 'Sign in to review, accept, and complete attachees at your branch. Marking an attachment complete generates the student\'s recommendation letter.',
                'need_account' => 'Branch admin accounts are created by the attachment provider from their Branches page.',
            ],
            'course_branch_admin' => [
                'badge' => $name,
                'heading' => 'Course branch admin sign in',
                'blurb' => 'Sign in to review student requests for your branch and manage your organisation\'s course categories.',
                'need_account' => 'Branch admin accounts are created by the organisation admin from their Branches page.',
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
