<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * Text messages through Africa's Talking (Super Admin → Settings: username,
 * API key, optional sender ID). Quietly does nothing until those are saved,
 * so an announcement still goes out by bell and email.
 */
class SmsService
{
    public static function configured(): bool
    {
        return trim((string) StoreSetting::get('sms_username')) !== '' && trim((string) StoreSetting::get('sms_api_key')) !== '';
    }

    /** Sends one message to many numbers (international format). Returns how many were accepted. */
    public static function send(array $phones, string $message): int
    {
        if (!self::configured() || !$phones) {
            return 0;
        }
        $user = trim((string) StoreSetting::get('sms_username'));
        $host = $user === 'sandbox' ? 'api.sandbox.africastalking.com' : 'api.africastalking.com';
        $sent = 0;
        foreach (array_chunk(array_values(array_unique($phones)), 100) as $chunk) {
            $fields = ['username' => $user, 'to' => implode(',', $chunk), 'message' => $message];
            if (trim((string) StoreSetting::get('sms_sender')) !== '') {
                $fields['from'] = trim((string) StoreSetting::get('sms_sender'));
            }
            $ch = curl_init("https://$host/version1/messaging");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($fields),
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'apiKey: ' . trim((string) StoreSetting::get('sms_api_key'))],
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            $data = is_string($body) ? json_decode($body, true) : null;
            $sent += count($data['SMSMessageData']['Recipients'] ?? []);
        }
        return $sent;
    }

    /** Kenyan numbers to +254… */
    public static function msisdn(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone);
        return strlen($d) >= 9 ? '+254' . substr($d, -9) : '';
    }
}
