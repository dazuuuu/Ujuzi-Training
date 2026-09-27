<?php

namespace App\Services;

use App\Core\Env;
use App\Models\StoreSetting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerException extends \Exception {}

/**
 * SMTP mailer (PHPMailer). Prefers Super Admin → Settings, then .env.
 */
class MailerService
{
    public static function config(): array
    {
        return [
            'host' => self::value('mail_host', 'MAIL_HOST', ''),
            'port' => self::value('mail_port', 'MAIL_PORT', '587'),
            'encryption' => self::value('mail_encryption', 'MAIL_ENCRYPTION', 'tls'),
            'username' => self::value('mail_username', 'MAIL_USERNAME', ''),
            'password' => self::value('mail_password', 'MAIL_PASSWORD', ''),
            'from_address' => self::value('mail_from_address', 'MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            'from_name' => self::value('mail_from_name', 'MAIL_FROM_NAME', Env::get('APP_NAME', 'Ujuzi Training')),
        ];
    }

    public static function isConfigured(): bool
    {
        $config = self::config();
        return $config['host'] !== ''
            && $config['username'] !== ''
            && $config['password'] !== ''
            && $config['host'] !== 'smtp.example.com';
    }

    private static function value(string $settingKey, string $envKey, $default = null): string
    {
        try {
            $stored = StoreSetting::get($settingKey);
            if ($stored !== null && $stored !== '') {
                return (string) $stored;
            }
        } catch (\Throwable $e) {
            // Settings table may not exist yet during setup.
        }
        return (string) Env::get($envKey, $default);
    }

    /**
     * A misconfigured/unreachable SMTP host must fail fast rather than hang
     * the request for PHPMailer's 300s default.
     */
    private static function configured(): PHPMailer
    {
        $config = self::config();
        if ($config['host'] === '' || $config['host'] === 'smtp.example.com' || $config['username'] === '' || $config['password'] === '') {
            throw new MailerException('SMTP is not configured. Open Super Admin → Settings and enter your mail host, username, and password.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Timeout = 10;
        $mail->SMTPKeepAlive = false;
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $encryption = strtolower($config['encryption']);
        $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($config['port'] ?: 587);
        $mail->setFrom($config['from_address'] ?: $config['username'], $config['from_name'] ?: 'Ujuzi Training');
        return $mail;
    }

    public static function sendOtp(string $toEmail, string $code, string $purpose = 'login'): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $isReset = $purpose === 'password_reset';
            $mail->isHTML(true);
            $mail->Subject = $isReset ? 'Your password reset code' : 'Your ' . Env::get('APP_NAME', 'Ujuzi Training') . ' login code';
            $mail->Body = self::otpHtml($code, $isReset);
            $mail->AltBody = ($isReset ? 'Your password reset code is: ' : 'Your login code is: ') . $code . ' (expires in 10 minutes).';
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send email: ' . $mail->ErrorInfo);
        }
    }

    public static function sendOrganisationAdminInvite(string $toEmail, string $registerUrl, string $organisationName, string $expiresAt): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Register as organisation admin for ' . $organisationName;
            $mail->Body = self::inviteHtml($registerUrl, $organisationName, $expiresAt);
            $mail->AltBody = 'Register as organisation admin for ' . $organisationName . ': ' . $registerUrl . ' This link expires at ' . $expiresAt . ' and can only be used once.';
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send email: ' . $mail->ErrorInfo);
        }
    }

    public static function sendAccountCreated(string $toEmail, string $loginUrl, string $roleLabel, string $password): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Your ' . Env::get('APP_NAME', 'Ujuzi Training') . ' account is ready';
            $mail->Body = self::accountCreatedHtml($loginUrl, $roleLabel, $toEmail, $password);
            $mail->AltBody = 'Your account is ready. Sign in at ' . $loginUrl . ' with email ' . $toEmail . ' and password ' . $password . '. You will be asked to set your own password on first login.';
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send email: ' . $mail->ErrorInfo);
        }
    }

    public static function sendRecommendationLetter(string $toEmail, string $studentName, string $providerName, string $letterUrl, string $note = ''): void
    {
        $mail = self::configured();
        try {
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Your attachment recommendation letter from ' . $providerName;
            $mail->Body = self::recommendationLetterHtml($studentName, $providerName, $letterUrl, $note);
            $mail->AltBody = 'Hello ' . $studentName . ', ' . $providerName . ' has sent you your attachment recommendation letter. Open it here: ' . $letterUrl
                . ($note !== '' ? "\n\n" . $note : '');
            $mail->send();
        } catch (PHPMailerException $e) {
            throw new MailerException('Could not send email: ' . $mail->ErrorInfo);
        }
    }

    private static function recommendationLetterHtml(string $studentName, string $providerName, string $letterUrl, string $note): string
    {
        $app = htmlspecialchars(Env::get('APP_NAME', 'Ujuzi Training'));
        $safeUrl = htmlspecialchars($letterUrl);
        $noteHtml = $note !== ''
            ? '<p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 16px;padding:12px;background:#f3f7f2;border-radius:8px;">' . nl2br(htmlspecialchars($note)) . '</p>'
            : '';
        return '
        <div style="font-family: Arial, sans-serif; background:#f3f7f2; padding:32px;">
          <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #c5d4cb;border-radius:12px;overflow:hidden;">
            <div style="background:#111111;padding:20px 24px;border-bottom:6px solid #bb0000;">
              <span style="color:#ffffff;font-weight:bold;letter-spacing:2px;font-size:14px;">' . $app . '</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#111111;margin:0 0 8px;">Your recommendation letter</h1>
              <p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 16px;">Hello ' . htmlspecialchars($studentName) . ', <strong>' . htmlspecialchars($providerName) . '</strong> has sent you your attachment recommendation letter.</p>
              ' . $noteHtml . '
              <p style="text-align:center;margin:0 0 20px;">
                <a href="' . $safeUrl . '" style="display:inline-block;background:#006b3f;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 18px;border-radius:8px;">Open your letter</a>
              </p>
              <p style="font-size:12px;color:#2f3f37;word-break:break-all;margin:0 0 12px;">' . $safeUrl . '</p>
              <p style="font-size:12px;color:#888;margin:0;">Sign in to your student portal to view or print it.</p>
            </div>
          </div>
        </div>';
    }

    private static function otpHtml(string $code, bool $isReset): string
    {
        $heading = $isReset ? 'Reset your password' : 'Your one-time login code';
        $blurb = $isReset
            ? 'Use the code below to verify it\'s you and set a new password.'
            : 'Use the code below to sign in to your dashboard.';
        return '
        <div style="font-family: Arial, sans-serif; background:#faf9f6; padding:32px;">
          <div style="max-width:420px;margin:0 auto;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
            <div style="background:#111111;padding:20px 24px;border-bottom:6px solid #bb0000;">
              <span style="color:#ffffff;font-weight:bold;letter-spacing:2px;font-size:14px;">' . htmlspecialchars(Env::get('APP_NAME', 'Ujuzi Training')) . '</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#111111;margin:0 0 8px;">' . htmlspecialchars($heading) . '</h1>
              <p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 20px;">' . htmlspecialchars($blurb) . '</p>
              <div style="background:#e8f5ee;border:2px solid #006b3f;border-radius:8px;padding:16px;text-align:center;margin-bottom:20px;">
                <span style="font-size:28px;letter-spacing:8px;font-weight:bold;color:#006b3f;">' . htmlspecialchars($code) . '</span>
              </div>
              <p style="font-size:12px;color:#888;margin:0;">This code expires in 10 minutes. If you didn\'t request this, you can safely ignore this email.</p>
            </div>
          </div>
        </div>';
    }

    private static function inviteHtml(string $registerUrl, string $organisationName, string $expiresAt): string
    {
        $app = htmlspecialchars(Env::get('APP_NAME', 'Ujuzi Training'));
        $safeUrl = htmlspecialchars($registerUrl);
        return '
        <div style="font-family: Arial, sans-serif; background:#f3f7f2; padding:32px;">
          <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #c5d4cb;border-radius:12px;overflow:hidden;">
            <div style="background:#111111;padding:20px 24px;border-bottom:6px solid #bb0000;">
              <span style="color:#ffffff;font-weight:bold;letter-spacing:2px;font-size:14px;">' . $app . '</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#111111;margin:0 0 8px;">Organisation admin registration</h1>
              <p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 16px;">You have been invited to register as organisation admin for <strong>' . htmlspecialchars($organisationName) . '</strong>.</p>
              <p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 20px;">This link expires in 5 minutes and can only be used once. After you register with your email and password, sign in to complete the profile forms assigned to organisation admins.</p>
              <p style="text-align:center;margin:0 0 20px;">
                <a href="' . $safeUrl . '" style="display:inline-block;background:#006b3f;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 18px;border-radius:8px;">Create your account</a>
              </p>
              <p style="font-size:12px;color:#2f3f37;word-break:break-all;margin:0 0 12px;">' . $safeUrl . '</p>
              <p style="font-size:12px;color:#888;margin:0;">Expires at ' . htmlspecialchars($expiresAt) . '. If you did not expect this, you can ignore this email.</p>
            </div>
          </div>
        </div>';
    }

    private static function accountCreatedHtml(string $loginUrl, string $roleLabel, string $email, string $password): string
    {
        $app = htmlspecialchars(Env::get('APP_NAME', 'Ujuzi Training'));
        $safeUrl = htmlspecialchars($loginUrl);
        return '
        <div style="font-family: Arial, sans-serif; background:#f3f7f2; padding:32px;">
          <div style="max-width:460px;margin:0 auto;background:#ffffff;border:1px solid #c5d4cb;border-radius:12px;overflow:hidden;">
            <div style="background:#111111;padding:20px 24px;border-bottom:6px solid #bb0000;">
              <span style="color:#ffffff;font-weight:bold;letter-spacing:2px;font-size:14px;">' . $app . '</span>
            </div>
            <div style="padding:28px 24px;">
              <h1 style="font-size:18px;color:#111111;margin:0 0 8px;">Your ' . htmlspecialchars($roleLabel) . ' account is ready</h1>
              <p style="font-size:13px;color:#2f3f37;line-height:1.5;margin:0 0 16px;">An account was created for you on ' . $app . '. Sign in with the details below.</p>
              <table style="width:100%;font-size:13px;color:#2f3f37;margin:0 0 20px;border-collapse:collapse;">
                <tr><td style="padding:4px 0;color:#888;">Email</td><td style="padding:4px 0;font-weight:bold;">' . htmlspecialchars($email) . '</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Password</td><td style="padding:4px 0;font-weight:bold;">' . htmlspecialchars($password) . '</td></tr>
              </table>
              <p style="text-align:center;margin:0 0 20px;">
                <a href="' . $safeUrl . '" style="display:inline-block;background:#006b3f;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 18px;border-radius:8px;">Sign in</a>
              </p>
              <p style="font-size:12px;color:#2f3f37;word-break:break-all;margin:0 0 12px;">' . $safeUrl . '</p>
              <p style="font-size:12px;color:#888;margin:0;">You will be asked to set your own password the first time you sign in.</p>
            </div>
          </div>
        </div>';
    }
}
