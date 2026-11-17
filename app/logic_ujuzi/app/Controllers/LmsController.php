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

    /**
     * A course code typed anywhere (public search, shared link): go to that
     * course. Signed-in people land on the course page (enrolment form if not
     * enrolled, otherwise they continue); everyone else logs in first and is
     * brought back to the course.
     */
    public function findByCode(?string $code = null): void
    {
        $code = $code ?? (string) \App\Core\Request::query('code', '');
        $id = Course::idByCode($code);
        if ($id === null) {
            \App\Core\UserSession::start();
            flashError('No course found with the code "' . trim($code) . '".');
            redirect('/courses');
        }
        \App\Core\UserSession::start();
        $target = '/account/courses/' . $id;
        if (!\App\Core\UserSession::current()) {
            $_SESSION['after_login'] = $target;
            flashSuccess('Log in (or create an account) to open this course.');
            redirect('/account/login');
        }
        redirect($target);
    }

    /** Opens a course by id from public search: login first if needed, then the course page. */
    public function openCourse(string $id): void
    {
        $course = Course::find((int) $id);
        $code = $course['course_code'] ?? '';
        if ($course && $code !== '') {
            $this->findByCode((string) $code);
        }
        \App\Core\UserSession::start();
        $target = '/account/courses/' . (int) $id;
        if (!\App\Core\UserSession::current()) {
            $_SESSION['after_login'] = $target;
            flashSuccess('Log in (or create an account) to open this course.');
            redirect('/account/login');
        }
        redirect($target);
    }

    /** JSON for the public courses search box: public courses by title, code, category, organisation or description. */
    public function searchCourses(): void
    {
        $q = mb_strtolower(trim((string) \App\Core\Request::query('q', '')));
        $out = [];
        if ($q !== '') {
            try {
                $courses = Course::searchPool();
            } catch (\Throwable $e) {
                $courses = [];
            }
            foreach ($courses as $c) {
                $code = (string) ($c['course_code'] ?? '');
                $title = mb_strtolower((string) $c['title']);
                $isGlobal = ($c['visibility'] ?? 'global') === 'global';
                if ($isGlobal) {
                    // Freely browsable: match on title, code, category, organisation or description.
                    $hay = $title . ' ' . mb_strtolower($code . ' ' . ($c['category_name'] ?? '') . ' ' . ($c['organisation_name'] ?? '') . ' ' . ($c['description'] ?? ''));
                    if (!str_contains($hay, $q)) {
                        continue;
                    }
                } else {
                    // Restricted to an organisation/branch: only its own code finds it, so it never shows up by a generic keyword search.
                    if ($code === '' || !str_starts_with(mb_strtolower($code), $q)) {
                        continue;
                    }
                }
                $score = (mb_strtolower($code) === $q ? 1000 : 0) + (str_starts_with(mb_strtolower($code), $q) ? 200 : 0) + (str_starts_with($title, $q) ? 100 : 0) + (str_contains($title, $q) ? 50 : 0);
                $fee = (float) ($c['enrollment_fee_ksh'] ?? 0);
                $out[] = ['score' => $score, 'title' => (string) $c['title'], 'code' => $code, 'url' => url('/open-course/' . (int) $c['id']),
                    'subtitle' => implode(' · ', array_filter([$code !== '' ? 'Code ' . $code : '', $c['category_name'] ?? '', $c['organisation_name'] ?? '', $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free'])),
                    'image' => !empty($c['cover_image']) && preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $c['cover_image']) ? imageUrl($c['cover_image']) : null];
            }
            usort($out, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
            $out = array_slice($out, 0, 8);
        }
        header('Content-Type: application/json');
        echo json_encode(['q' => $q, 'courses' => $out]);
        exit;
    }

    /** The public Exams page: courses that are only an exam (NTSA, NITA…). Same page as Courses, different list. */
    public function exams(): void
    {
        try {
            $courses = Course::publicExams();
        } catch (\Throwable $e) {
            $courses = [];
        }
        View::render('lms.courses', ['courses' => $courses, 'examsPage' => true]);
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
