<?php

namespace App\Services;

use App\Core\Database;
use App\Core\ErrorLog;
use App\Core\Env;

/**
 * "What is the error?" — checks the things that usually break the site and
 * says what is wrong in plain words, with the real database message.
 * Every check is wrapped so one failure never hides the others.
 */
class SystemCheck
{
    /** @return array<int, array{name:string, status:string, detail:string}> status: ok | warn | fail */
    public static function run(): array
    {
        $out = [];
        $add = static function (string $name, string $status, string $detail) use (&$out): void {
            $out[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
        };

        $add('PHP version', version_compare(PHP_VERSION, '8.1', '>=') ? 'ok' : 'warn', PHP_VERSION);
        foreach (['pdo_mysql', 'mbstring', 'curl', 'json', 'openssl', 'fileinfo'] as $ext) {
            $add('PHP extension: ' . $ext, extension_loaded($ext) ? 'ok' : ($ext === 'curl' ? 'warn' : 'fail'),
                extension_loaded($ext) ? 'installed' : 'missing' . ($ext === 'curl' ? ' — M-Pesa, PayPal and SMS need it' : ''));
        }

        $pdo = null;
        try {
            $pdo = Database::connection();
            $add('Database connection', 'ok', 'Connected to ' . Env::get('DB_NAME') . ' on ' . Env::get('DB_HOST'));
        } catch (\Throwable $e) {
            $add('Database connection', 'fail', $e->getMessage() . ' — check the DB_* values in Core/Database.php (host, name, user, password).');
        }

        if ($pdo) {
            try {
                $pending = MigrationService::pending();
                $add('Updates', $pending ? 'warn' : 'ok', $pending ? count($pending) . ' not installed yet: ' . implode(', ', array_slice($pending, 0, 6)) . ' — Admin → Updates → Install.' : 'All installed');
            } catch (\Throwable $e) {
                $add('Updates', 'fail', $e->getMessage());
            }
            foreach (['users', 'courses', 'course_enrollments', 'wallet_transactions', 'store_settings', 'organisation_memberships'] as $table) {
                try {
                    $pdo->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
                    $add('Table: ' . $table, 'ok', 'present');
                } catch (\Throwable $e) {
                    $add('Table: ' . $table, 'fail', $e->getMessage());
                }
            }
            foreach (['mpesa_payments', 'paypal_payments', 'login_audit', 'announcements'] as $table) {
                try {
                    $pdo->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
                    $add('Table: ' . $table, 'ok', 'present');
                } catch (\Throwable $e) {
                    $add('Table: ' . $table, 'warn', 'missing — install the pending updates');
                }
            }
            try {
                $bad = (int) $pdo->query("SELECT COUNT(DISTINCT table_collation) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'")->fetchColumn();
                $add('Table collations', $bad > 1 ? 'warn' : 'ok', $bad > 1 ? $bad . ' different collations in use — can cause "Illegal mix of collations".' : 'consistent');
            } catch (\Throwable $e) {
            }
        }

        $storage = dirname(__DIR__, 2) . '/storage';
        $uploads = dirname(__DIR__, 4) . '/public/assets/uploads';
        foreach (['Logs folder' => $storage . '/logs', 'Uploads folder' => $uploads] as $label => $dir) {
            $add($label, is_dir($dir) && is_writable($dir) ? 'ok' : 'fail', is_dir($dir) ? (is_writable($dir) ? 'writable' : 'not writable: ' . $dir) : 'missing: ' . $dir);
        }
        if ($pdo) {
            try {
            $add('Email', \App\Services\MailerService::isConfigured() ? 'ok' : 'warn', \App\Services\MailerService::isConfigured() ? 'configured' : 'not configured — login codes and notices cannot be emailed');
            $add('SMS', SmsService::configured() ? 'ok' : 'warn', SmsService::configured() ? 'configured' : 'not configured (optional)');
            $add('M-Pesa', PaymentGateway::mode() === 'live' ? 'ok' : 'warn', PaymentGateway::mode() === 'live' ? 'live' : 'test mode — no real money moves');
            $add('PayPal', PayPalService::configured() ? 'ok' : 'warn', PayPalService::configured() ? 'configured' : 'not configured (optional)');
            } catch (\Throwable $e) {
                $add('Integrations', 'fail', $e->getMessage());
            }
        } else {
            $add('Email, SMS, M-Pesa, PayPal', 'warn', 'not checked — they are saved in the database, which cannot be reached');
        }

        return $out;
    }

    /**
     * The distinct recent errors from the error log, newest first, each with a
     * count and a plain hint. @return array<int, array{message:string, count:int, last:string, hint:string}>
     */
    public static function recentErrors(int $lines = 3000): array
    {
        $text = ErrorLog::tail($lines);
        if (trim($text) === '') {
            return [];
        }
        $groups = [];
        foreach (preg_split('/\n(?=\[\d{2}-\w{3}-\d{4} )/', $text) ?: [] as $entry) {
            if (!preg_match('/^\[([^\]]+)\] (.*)/', $entry, $m)) {
                continue;
            }
            $lines2 = explode("\n", $entry);
            $headline = trim($m[2]);
            if (str_starts_with($headline, 'Uncaught') && isset($lines2[1])) {
                $headline = trim($lines2[1]); // the exception line is more useful than "Uncaught on GET…"
            }
            $key = preg_replace('/\d+/', '#', $headline);
            $groups[$key] ??= ['message' => $headline, 'count' => 0, 'last' => $m[1], 'hint' => self::hint($headline)];
            $groups[$key]['count']++;
            $groups[$key]['last'] = $m[1];
        }
        return array_reverse(array_values($groups));
    }

    private static function hint(string $m): string
    {
        $map = [
            '/SQLSTATE\[HY000\] \[(1045|1044)\]/' => 'Wrong database user or password (Core/Database.php).',
            '/SQLSTATE\[HY000\] \[(2002|2005)\]/' => 'The database server cannot be reached — check DB_HOST.',
            '/Unknown database/i' => 'The database name does not exist — check DB_NAME.',
            "/Base table or view not found|doesn't exist/i" => 'A table is missing — run Admin → Updates → Install.',
            '/Unknown column/i' => 'A column is missing — run Admin → Updates → Install.',
            '/1832|foreign key/i' => 'A foreign key blocks the change (the collation update now skips such tables).',
            '/Illegal mix of collations/i' => 'Tables use different collations — install the "Unify table collations" update.',
            '/Class .* not found/i' => 'A file is missing or in the wrong place on the server — re-upload the project.',
            '/Cannot access offset of type/i' => 'A page received data in an unexpected shape — send this error to the developer.',
            '/Allowed memory size/i' => 'PHP ran out of memory — raise memory_limit or reduce the data.',
            '/Permission denied|failed to open stream/i' => 'The server cannot write a file — check folder permissions.',
        ];
        foreach ($map as $re => $hint) {
            if (preg_match($re, $m)) {
                return $hint;
            }
        }
        return '';
    }
}
