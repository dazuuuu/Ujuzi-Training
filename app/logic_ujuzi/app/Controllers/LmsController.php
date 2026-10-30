<?php

namespace App\Controllers;

use App\Core\AdminSession;
use App\Core\View;
use App\Models\Admin;
use App\Models\Course;
use App\Models\Role;
use App\Services\MigrationService;

class LmsController
{
    public function home(): void
    {
        $roles = [];
        $needsSetup = false;
        $pendingMigrations = [];
        $isSuperAdmin = false;
        try {
            $roles = Role::all();
            $needsSetup = Admin::count() === 0;
        } catch (\Throwable $e) {
            $needsSetup = true;
        }

        try {
            AdminSession::start();
            $isSuperAdmin = AdminSession::current() !== null;
            $pendingMigrations = MigrationService::pending();
        } catch (\Throwable $e) {
            $pendingMigrations = [];
        }

        View::render('lms.home', [
            'roles' => $roles,
            'needsSetup' => $needsSetup,
            'pendingMigrations' => $pendingMigrations,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function courses(): void
    {
        try {
            $courses = Course::publicListing();
        } catch (\Throwable $e) {
            $courses = [];
        }

        View::render('lms.courses', [
            'courses' => $courses,
        ]);
    }

    public function about(): void
    {
        View::render('lms.about', []);
    }

    /** Public: anyone checks a student by the number on their certificate. */
    public function lookup(): void
    {
        $number = trim((string) ($_GET['reg'] ?? ''));
        View::render('lms.lookup', [
            'lookupNumber' => $number,
            'lookupRecord' => $number !== '' ? \App\Services\PublicLookup::find($number) : null,
        ]);
    }

    public function contact(): void
    {
        \App\Core\UserSession::start(); // the form's CSRF token lives in the session
        View::render('lms.contact', []);
    }

    /** The contact form: emailed to the contact email Super Admin set. */
    public function sendContact(): void
    {
        \App\Core\UserSession::start();
        $back = static fn(string $state) => redirect('/contact?' . $state . '#contact-form');
        if (!csrfVerify($_POST['csrf_token'] ?? null) || ($_POST['website'] ?? '') !== '') {
            $back('sent=0'); // expired form, or a bot filled the hidden field
        }
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 120);
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 40);
        $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 4000);
        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $back('sent=invalid');
        }
        if (time() - (int) ($_SESSION['contact_sent_at'] ?? 0) < 60) {
            $back('sent=wait');
        }
        $to = trim((string) (\App\Services\PageBuilder::site()['contact_email'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !\App\Services\MailerService::isConfigured()) {
            $back('sent=0');
        }
        try {
            \App\Services\MailerService::sendNotice(
                $to,
                'Website message from ' . $name,
                'New message from the contact page',
                "From: $name\nEmail: $email" . ($phone !== '' ? "\nPhone: $phone" : '') . "\n\n$message",
                'Reply by email',
                'mailto:' . $email
            );
            $_SESSION['contact_sent_at'] = time();
            $back('sent=1');
        } catch (\Throwable $e) {
            $back('sent=0');
        }
    }

    /** Footer newsletter sign-up: saved for Super Admin (Pages → Newsletter subscribers). */
    public function subscribe(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $ok = ($_POST['website'] ?? '') === '' && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 190;
        if ($ok) {
            try {
                \App\Core\Database::connection()->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)')->execute([$email]);
            } catch (\Throwable $e) {
                $ok = false; // before the newsletter update
            }
        }
        // Back to the page they were on (this site only).
        $back = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '/';
        $back = str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : '/';
        header('Location: ' . $back . '?subscribed=' . ($ok ? '1' : '0') . '#newsletter', true, 303);
        exit;
    }

    /** A page Super Admin made in the page builder (/p/{slug}). Unpublished pages show only in the editor's preview. */
    public function customPage(string $slug): void
    {
        $meta = \App\Services\PageBuilder::customPages()[$slug] ?? null;
        if (!$meta || (empty($meta['published']) && \App\Services\PageBuilder::previewToken() === null)) {
            http_response_code(404);
            View::render('lms.404');
            return;
        }
        View::render('lms.custom', ['pbPage' => 'c-' . $slug, 'pbTitle' => $meta['title'] . ' | ' . appName(), 'pbDescription' => $meta['description']]);
    }
}
