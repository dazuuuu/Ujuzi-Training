<?php

namespace App\Services;

use App\Models\OtpCode;

/**
 * Business-logic layer over OtpCode (pure data access) + MailerService
 * (delivery) — used by Account\AuthController for email-based login.
 */
class OtpService
{
    /** @throws MailerException */
    public static function issueAndSend(int $customerId, string $email, string $purpose = 'login'): void
    {
        $code = OtpCode::generate($customerId, $purpose);
        MailerService::sendOtp($email, $code, $purpose);
    }

    public static function verify(int $customerId, string $code, string $purpose = 'login'): bool
    {
        return OtpCode::verify($customerId, $code, $purpose);
    }
}
