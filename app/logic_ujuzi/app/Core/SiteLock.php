<?php

namespace App\Core;

use App\Models\Admin;
use App\Services\MailerException;
use App\Services\MailerService;

/**
 * A kill switch for sign-ins, controlled only by the hardcoded account in
 * App\Core\SystemAccount. When engaged, nobody can sign in — not the Super
 * Admin created at /setup, not any other Super Admin, not a tutor, student,
 * organisation or branch. Nothing on screen says why: each login attempt
 * lands back on its own login page with the normal "incorrect" message, so
 * a visitor sees only that, never a maintenance notice. The System Account
 * itself can still always sign in, so it can lift the lock again.
 *
 * Every admin's own email, and the System Account's address, get a single
 * email ("please settle the outstanding balance...") the moment the lock is
 * switched on — not on every later attempt, which is why a timestamp is kept
 * alongside the flag.
 */
class SiteLock
{
    private const LOCK_KEY = 'site_lock_engaged';
    private const MESSAGE_KEY = 'site_lock_note';
    private const NOTIFIED_KEY = 'site_lock_notified_at';

    /** Settings keys this owns — kept out of the regular Settings screen and export. */
    public static function reservedKeys(): array
    {
        return [self::LOCK_KEY, self::MESSAGE_KEY, self::NOTIFIED_KEY];
    }

    /** Reads a key straight from the database, bypassing StoreSetting (which hides these). */
    private static function raw(string $key): string
    {
        $stmt = Database::connection()->prepare('SELECT setting_value FROM store_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? '' : (string) $value;
    }

    private static function put(string $key, ?string $value): void
    {
        Database::connection()->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
    }

    public static function isLocked(): bool
    {
        try {
            return self::raw(self::LOCK_KEY) === '1';
        } catch (\Throwable $e) {
            return false; // no database/settings table yet
        }
    }

    public static function note(): string
    {
        try {
            return self::raw(self::MESSAGE_KEY);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Switches the lock on or off. Turning it on sends the one-time notice. */
    public static function setLocked(bool $locked, string $note = ''): void
    {
        self::put(self::LOCK_KEY, $locked ? '1' : '0');
        self::put(self::MESSAGE_KEY, $note);
        if ($locked) {
            self::notify($note);
        } else {
            self::put(self::NOTIFIED_KEY, '');
        }
    }

    /**
     * Blocks a sign-in attempt while the lock is on. Returns true (and sends
     * the notice, the first time) when the attempt should be refused outright
     * — before any password is even checked — so no account, however valid
     * its credentials, can get in except the System Account.
     */
    public static function blocksLogin(): bool
    {
        if (!self::isLocked()) {
            return false;
        }
        self::notify(self::note());
        return true;
    }

    /** One email per lock engagement, not one per attempt. */
    private static function notify(string $note): void
    {
        try {
            if (self::raw(self::NOTIFIED_KEY) !== '') {
                return;
            }
            self::put(self::NOTIFIED_KEY, (string) time());
        } catch (\Throwable $e) {
            return;
        }

        $message = trim($note) !== ''
            ? $note
            : 'Access to the system has been suspended. Please settle the outstanding balance to avoid further inconvenience.';

        $recipients = [SystemAccount::email()];
        try {
            foreach (Admin::all() as $admin) {
                if (!empty($admin['email'])) {
                    $recipients[] = (string) $admin['email'];
                }
            }
        } catch (\Throwable $e) {
            // No admins table yet.
        }

        foreach (array_unique(array_filter($recipients)) as $to) {
            try {
                MailerService::sendNotice(
                    $to,
                    'Action required: sign-ins are suspended',
                    'Sign-ins are currently suspended',
                    $message,
                    'Open Super Admin',
                    Url::to('/admin/login')
                );
            } catch (MailerException|\Throwable $e) {
                // Best effort — a failed notice must never surface to a visitor.
            }
        }
    }
}
