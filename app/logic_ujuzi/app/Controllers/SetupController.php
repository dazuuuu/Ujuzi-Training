<?php

namespace App\Controllers;

use App\Core\AdminSession;
use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\View;
use App\Models\Admin;
use App\Models\StoreSetting;
use App\Services\MailerService;
use App\Services\MigrationService;
use App\Services\PageBuilder;
use App\Services\UploadException;
use App\Services\UploadService;

/**
 * The installer: checks the server, connects the database, runs the
 * database updates, sets up the platform and email, and creates the Super
 * Admin. Once a Super Admin exists (or setup was finished) it is locked and
 * sends everyone to the admin sign-in.
 */
class SetupController
{
    public const STEPS = [
        'requirements' => 'Server check',
        'database' => 'Database',
        'migrations' => 'Database updates',
        'platform' => 'Platform',
        'mail' => 'Email',
        'devs' => 'Developer account',
        'admin' => 'Super Admin',
    ];

    public function __construct()
    {
        AdminSession::start();
    }

    public function index(): void
    {
        $this->guardLocked();
        // Best effort: make the uploads folder usable without stopping setup.
        Env::makeWritable(dirname(__DIR__, 4) . '/public/assets/uploads');
        $step = $this->step();
        $this->show($step, []);
    }

    public function store(): void
    {
        $this->guardLocked();
        $step = $this->step();
        if (!csrfVerify(Request::post('csrf_token'))) {
            $this->show($step, ['Your session expired. Please submit the form again.']);
            return;
        }
        $errors = match ($step) {
            'requirements' => $this->saveRequirements(),
            'database' => $this->saveDatabase(),
            'migrations' => $this->saveMigrations(),
            'platform' => $this->savePlatform(),
            'mail' => $this->saveMail(),
            'devs' => $this->saveDevs(),
            'admin' => $this->saveAdmin(),
        };
        $this->show($step, $errors);
    }

    // --- Steps -------------------------------------------------------------

    private function saveRequirements(): array
    {
        foreach (self::requirements() as $check) {
            if ($check['required'] && !$check['ok']) {
                return ['Fix the items marked "Needed" first, then check again.'];
            }
        }
        $this->next('database');
    }

    private function saveDatabase(): array
    {
        $values = [
            'DB_HOST' => trim((string) Request::post('db_host', '127.0.0.1')) ?: '127.0.0.1',
            'DB_PORT' => (string) ((int) Request::post('db_port', 3306) ?: 3306),
            'DB_NAME' => trim((string) Request::post('db_name', '')),
            'DB_USER' => trim((string) Request::post('db_user', '')),
            'DB_PASS' => (string) Request::post('db_pass', ''),
        ];
        if (!preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $values['DB_NAME'])) {
            return ['Give a database name using letters, numbers, _ or - only.'];
        }
        if ($values['DB_USER'] === '') {
            return ['Give the database user name.'];
        }
        // Try the typed password, then the one in Database.php (a blank box, or
        // one the browser filled in by itself, falls back to the saved password).
        $saved = (string) (\App\Core\Database::SETTINGS['DB_PASS'] ?? '');
        $candidates = array_values(array_unique([$values['DB_PASS'], $saved, (string) Env::get('DB_PASS', '')], SORT_STRING));
        if ($values['DB_PASS'] === '' && $saved !== '') {
            $candidates = array_values(array_unique([$saved, (string) Env::get('DB_PASS', ''), ''], SORT_STRING));
        }
        $error = null;
        foreach ($candidates as $password) {
            try {
                $pdo = new \PDO(
                    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $values['DB_HOST'], $values['DB_PORT']),
                    $values['DB_USER'],
                    $password,
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]
                );
                $values['DB_PASS'] = $password;
                $error = null;
                break;
            } catch (\PDOException $e) {
                $error = $e;
                if ((int) $e->getCode() !== 1045) {
                    break; // not a wrong password (e.g. server unreachable): another password won't help
                }
            }
        }
        if ($error) {
            return [(int) $error->getCode() === 1045
                ? 'The database refused the user "' . $values['DB_USER'] . '" with the password typed and with the password in Database.php. Check the user name and password in your hosting panel (cPanel → MySQL Databases), and that the user is added to the database.'
                : 'Could not connect: ' . $error->getMessage()];
        }
        try {
            if (Request::post('create_db') === '1') {
                try {
                    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $values['DB_NAME'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                } catch (\PDOException $e) {
                    // Shared hosting users often can't create databases: fine if it already exists.
                }
            }
            $pdo->exec('USE `' . $values['DB_NAME'] . '`');
        } catch (\PDOException $e) {
            return ['Connected, but could not open the database "' . $values['DB_NAME'] . '": ' . $e->getMessage() . ' — create it in your hosting panel and add the user to it.'];
        }
        if (!Env::save($values)) {
            // Carry on with the settings held for this session; they're shown at the end to copy in.
            $_SESSION['setup_manual_config'] = array_merge($_SESSION['setup_manual_config'] ?? [], $values);
        }
        Database::reset();
        if ($this->adminExists()) {
            // This database already has a Super Admin: the site is installed.
            Env::save(['INSTALLED' => '1']);
            flashSuccess('Connected to an existing installation. Sign in as Super Admin.');
            redirect('/admin/login');
        }
        $this->next('migrations');
    }

    private function saveMigrations(): array
    {
        if (!self::databaseReachable()) {
            return ['The database is not reachable. Go back to the Database step.'];
        }
        try {
            $ran = MigrationService::runPending();
        } catch (\Throwable $e) {
            return ['A database update failed: ' . $e->getMessage() . ' — fix the cause and run the updates again (finished steps are skipped).'];
        }
        flashSuccess($ran . ' database update' . ($ran === 1 ? '' : 's') . ' ran.');
        $this->next('platform');
    }

    private function savePlatform(): array
    {
        if (!self::databaseReachable() || MigrationService::pending()) {
            return ['Run the database updates first.'];
        }
        $name = mb_substr(trim((string) Request::post('platform_name', '')), 0, 120);
        $url = rtrim(trim((string) Request::post('app_url', '')), '/');
        $timezone = (string) Request::post('timezone', 'Africa/Nairobi');
        $contact = trim((string) Request::post('contact_email', ''));
        $errors = [];
        if ($name === '') {
            $errors[] = 'Give the platform a name.';
        }
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'The site address must be a full URL, e.g. https://example.com.';
        }
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $errors[] = 'Choose a valid time zone.';
        }
        if ($contact !== '' && !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'The contact email is not valid.';
        }
        $file = Request::file('store_logo');
        $logo = null;
        if (!$errors && $file && !empty($file['name'])) {
            try {
                $logo = UploadService::store($file, 'settings');
            } catch (UploadException $e) {
                $errors[] = 'Logo: ' . $e->getMessage();
            }
        }
        if ($errors) {
            return $errors;
        }
        StoreSetting::set('platform_name', $name);
        if ($logo) {
            StoreSetting::set('store_logo', $logo);
        }
        if ($contact !== '') {
            PageBuilder::saveSite(array_merge(PageBuilder::site(), ['contact_email' => $contact]));
        }
        $appValues = [
            'APP_NAME' => $name,
            'APP_URL' => $url,
            'APP_TIMEZONE' => $timezone,
            'APP_DEBUG' => Request::post('app_debug') === '1' ? '1' : '0',
        ];
        if (!Env::save($appValues)) {
            $_SESSION['setup_manual_config'] = array_merge($_SESSION['setup_manual_config'] ?? [], $appValues);
        }
        $this->next('mail');
    }

    private function saveMail(): array
    {
        if (!self::databaseReachable() || MigrationService::pending()) {
            return ['Run the database updates first.'];
        }
        if (Request::post('skip') === '1') {
            $this->next('admin');
        }
        $host = trim((string) Request::post('mail_host', ''));
        $from = trim((string) Request::post('mail_from_address', ''));
        if ($host === '') {
            return ['Give the SMTP server, or choose "Skip for now".'];
        }
        if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return ['The "from" address is not a valid email.'];
        }
        StoreSetting::set('mail_host', $host);
        StoreSetting::set('mail_port', (string) ((int) Request::post('mail_port', 587) ?: 587));
        StoreSetting::set('mail_encryption', Request::post('mail_encryption') === 'ssl' ? 'ssl' : 'tls');
        StoreSetting::set('mail_username', trim((string) Request::post('mail_username', '')) ?: null);
        if ((string) Request::post('mail_password', '') !== '') {
            StoreSetting::set('mail_password', (string) Request::post('mail_password'));
        }
        StoreSetting::set('mail_from_address', $from ?: null);
        StoreSetting::set('mail_from_name', trim((string) Request::post('mail_from_name', '')) ?: null);

        $testTo = trim((string) Request::post('test_to', ''));
        if ($testTo !== '') {
            if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                return ['Settings saved, but the test address is not a valid email.'];
            }
            try {
                MailerService::sendNotice($testTo, 'Test email from ' . appName(), 'Email works', 'This test was sent from the setup page. Emails such as sign-in codes and invitations will reach people.', 'Open the site', \App\Core\Url::absolute('/'));
            } catch (\Throwable $e) {
                return ['Settings saved, but the test email failed: ' . $e->getMessage() . ' — check them and try again, or skip.'];
            }
            flashSuccess('Test email sent to ' . $testTo . '.');
        }
        $this->next('devs');
    }

    /**
     * The private developer account that can lock the whole site (see
     * App\Core\DevGate). Set up before the Super Admin and kept entirely
     * separate from it. Leaving the password blank keeps the existing one.
     */
    private function saveDevs(): array
    {
        if (!self::databaseReachable() || MigrationService::pending()) {
            return ['Run the database updates first.'];
        }
        $email = strtolower(trim((string) Request::post('email', '')));
        $password = (string) Request::post('password', '');
        $confirm = (string) Request::post('password_confirm', '');
        $alreadySet = \App\Core\DevGate::configured();
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid developer email address.';
        }
        if ($password === '' && !$alreadySet) {
            $errors[] = 'Set a developer password (at least 8 characters).';
        }
        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'The developer password must be at least 8 characters.';
        }
        if ($password !== '' && $password !== $confirm) {
            $errors[] = 'The developer passwords do not match.';
        }
        if ($errors) {
            $_SESSION['setup_devs_old'] = ['email' => $email];
            return $errors;
        }
        if ($password !== '') {
            \App\Core\DevGate::setCredentials($email, $password);
        } else {
            \App\Core\DevGate::setEmail($email);
        }
        unset($_SESSION['setup_devs_old']);
        $this->next('admin');
    }

    private function saveAdmin(): array
    {
        if (!self::databaseReachable() || MigrationService::pending()) {
            return ['Run the database updates first.'];
        }
        $email = strtolower(trim((string) Request::post('email', '')));
        $name = trim((string) Request::post('name', ''));
        $password = (string) Request::post('password', '');
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'The password must be at least 8 characters.';
        }
        if ($password !== (string) Request::post('password_confirm', '')) {
            $errors[] = 'The passwords do not match.';
        }
        if ($errors) {
            $_SESSION['setup_old'] = ['email' => $email, 'name' => $name];
            return $errors;
        }
        $adminId = Admin::create($email, $password, $name !== '' ? $name : null);
        $saved = Env::save(['INSTALLED' => '1']);
        $manual = $_SESSION['setup_manual_config'] ?? [];
        unset($_SESSION['setup_old'], $_SESSION['setup_manual_config'], $_SESSION['env_override']);
        AdminSession::loginAdmin($adminId, $email, $name !== '' ? $name : null);
        if (!$saved && $manual) {
            unset($manual['DB_PASS']);
            $lines = [];
            foreach ($manual as $key => $value) {
                $lines[] = "'" . $key . "' => " . var_export($value, true) . ',';
            }
            flashError('Setup finished, but Database.php could not be written. Put these in Database::SETTINGS (app/Core/Database.php), plus your DB_PASS: ' . implode(' ', $lines));
        }
        flashSuccess('Setup finished. Next: roles and forms, homepage, payments and organisations — all under Super Admin.');
        redirect('/admin');
    }

    // --- Helpers -------------------------------------------------------------

    /** [label, ok, detail, required] for each server requirement. */
    public static function requirements(): array
    {
        $ext = static fn(string $name, string $why, bool $required = true): array => [
            'label' => 'PHP extension: ' . $name, 'ok' => extension_loaded($name), 'detail' => $why, 'required' => $required,
        ];
        return [
            ['label' => 'PHP 8.1 or newer', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => 'Running PHP ' . PHP_VERSION, 'required' => true],
            $ext('pdo_mysql', 'Connects to the MySQL / MariaDB database.'),
            $ext('mbstring', 'Handles names and text in any language.'),
            $ext('json', 'Stores forms, answers and settings.'),
            $ext('openssl', 'Secure email (SMTP over TLS) and payments.'),
            $ext('fileinfo', 'Checks uploaded files are what they claim to be.'),
            $ext('zip', 'Excel (.xlsx) downloads. Without it, lists download as CSV.', false),
            $ext('gd', 'Resizes uploaded pictures.', false),
            $ext('curl', 'Talks to payment providers.', false),
        ];
    }

    public static function databaseReachable(): bool
    {
        try {
            Database::connection()->query('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function adminExists(): bool
    {
        try {
            return Admin::count() > 0;
        } catch (\Throwable $e) {
            return false; // no tables yet
        }
    }

    /** Finished installs (or a database that already has a Super Admin) never show setup again. */
    private function guardLocked(): void
    {
        if (Env::get('INSTALLED') === '1' || (self::databaseReachable() && $this->adminExists())) {
            redirect('/admin/login');
        }
    }

    private function step(): string
    {
        $step = (string) Request::query('step', 'requirements');
        return isset(self::STEPS[$step]) ? $step : 'requirements';
    }

    private function next(string $step): never
    {
        redirect('/setup?step=' . $step);
    }

    private function show(string $step, array $errors): void
    {
        $dbOk = self::databaseReachable();
        $pending = [];
        if ($dbOk) {
            try {
                $pending = MigrationService::pending();
            } catch (\Throwable $e) {
                $pending = [];
            }
        }
        $timezones = \DateTimeZone::listIdentifiers();
        View::render('setup.index', [
            'step' => $step,
            'steps' => self::STEPS,
            'errors' => $errors,
            'requirements' => self::requirements(),
            'dbOk' => $dbOk,
            'db' => [
                'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
                'port' => (string) Env::get('DB_PORT', '3306'),
                'name' => (string) Env::get('DB_NAME', ''),
                'user' => (string) Env::get('DB_USER', ''),
                'has_pass' => (string) Env::get('DB_PASS', '') !== '',
            ],
            'manualConfig' => $_SESSION['setup_manual_config'] ?? null,
            'pending' => $pending,
            'platform' => [
                'name' => $dbOk ? appName() : (string) Env::get('APP_NAME', ''),
                'url' => (string) Env::get('APP_URL', '') ?: \App\Core\Url::absolute(''),
                'timezone' => (string) Env::get('APP_TIMEZONE', 'Africa/Nairobi'),
                'debug' => Env::get('APP_DEBUG', '1') === '1',
                'contact' => $dbOk && !$pending ? (string) (PageBuilder::site()['contact_email'] ?? '') : '',
            ],
            'timezones' => $timezones,
            'mail' => $dbOk && !$pending ? MailerService::config() : [],
            'old' => $_SESSION['setup_old'] ?? ['email' => '', 'name' => ''],
            'devsAccount' => [
                'email' => ($dbOk && !$pending) ? (($_SESSION['setup_devs_old']['email'] ?? '') ?: \App\Core\DevGate::email()) : '',
                'configured' => ($dbOk && !$pending) && \App\Core\DevGate::configured(),
            ],
        ]);
    }
}
