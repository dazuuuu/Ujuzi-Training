<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * The single place money actually moves in from outside. Wallet deposits call
 * charge(); everything else in the wallet code is gateway-agnostic.
 *
 * Right now only "simulation" mode exists: it approves every request straight
 * away and returns a fake reference. To go live, implement the Daraja STK push
 * inside chargeLive() (the Daraja keys are already saved under Super Admin ->
 * Settings) and set the store setting wallet_payment_mode to "live".
 */
class PaymentGateway
{
    /**
     * Automatic: live as soon as the Daraja keys are saved in Super Admin ->
     * Settings. Set wallet_payment_mode to "simulation" to force fake payments.
     */
    public static function mode(): string
    {
        $forced = StoreSetting::get('wallet_payment_mode');
        if ($forced === 'simulation') {
            return 'simulation';
        }
        return $forced === 'live' || self::configured() ? 'live' : 'simulation';
    }

    public static function configured(): bool
    {
        foreach (['daraja_consumer_key', 'daraja_consumer_secret', 'daraja_passkey', 'daraja_shortcode'] as $k) {
            if (trim((string) StoreSetting::get($k)) === '') {
                return false;
            }
        }
        return true;
    }

    /** @return array{provider:string, reference:string, pending?:bool, merchant_request_id?:string} */
    public static function charge(string $phone, float $amountKsh): array
    {
        return self::mode() === 'live'
            ? self::chargeLive($phone, $amountKsh)
            : self::chargeSimulated($phone, $amountKsh);
    }

    private static function chargeSimulated(string $phone, float $amountKsh): array
    {
        return ['provider' => 'simulation', 'reference' => 'SIM' . strtoupper(bin2hex(random_bytes(5)))];
    }

    public static function callbackUrl(): string
    {
        $set = trim((string) StoreSetting::get('daraja_callback_url'));
        return $set !== '' ? $set : rtrim((string) \url('/payments/mpesa/callback'), '/');
    }

    private static function base(): string
    {
        return StoreSetting::get('daraja_environment') === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    private static function call(string $method, string $path, ?array $json, array $headers): array
    {
        $ch = curl_init(self::base() . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers + ($json !== null ? [] : []),
        ]);
        if ($json !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new PaymentException('Could not reach M-Pesa. Please try again. ' . $err);
        }
        return json_decode((string) $body, true) ?: [];
    }

    private static function token(): string
    {
        $auth = base64_encode(trim((string) StoreSetting::get('daraja_consumer_key')) . ':' . trim((string) StoreSetting::get('daraja_consumer_secret')));
        $res = self::call('GET', '/oauth/v1/generate?grant_type=client_credentials', null, ['Authorization: Basic ' . $auth]);
        if (empty($res['access_token'])) {
            throw new PaymentException('M-Pesa rejected the saved Daraja keys. Ask the administrator to check them.');
        }
        return (string) $res['access_token'];
    }

    /** Sends the STK push prompt to the phone; the money arrives later via callback. */
    private static function chargeLive(string $phone, float $amountKsh): array
    {
        $msisdn = preg_replace('/\D+/', '', $phone);
        $msisdn = '254' . substr($msisdn, -9);
        $shortcode = trim((string) StoreSetting::get('daraja_shortcode'));
        $till = StoreSetting::get('daraja_account_type') === 'till';
        $partyB = $till && trim((string) StoreSetting::get('daraja_till_number')) !== '' ? trim((string) StoreSetting::get('daraja_till_number')) : $shortcode;
        $time = date('YmdHis');
        $res = self::call('POST', '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode . trim((string) StoreSetting::get('daraja_passkey')) . $time),
            'Timestamp' => $time,
            'TransactionType' => $till ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
            'Amount' => (int) ceil($amountKsh),
            'PartyA' => $msisdn,
            'PartyB' => $partyB,
            'PhoneNumber' => $msisdn,
            'CallBackURL' => self::callbackUrl(),
            'AccountReference' => substr(preg_replace('/[^A-Za-z0-9]/', '', (string) \appName()) ?: 'Wallet', 0, 12),
            'TransactionDesc' => 'Wallet top up',
        ], ['Content-Type: application/json', 'Authorization: Bearer ' . self::token()]);
        if (($res['ResponseCode'] ?? '') !== '0' || empty($res['CheckoutRequestID'])) {
            throw new PaymentException('M-Pesa could not start the payment: ' . ($res['errorMessage'] ?? $res['ResponseDescription'] ?? 'unknown error') . '.');
        }
        return ['provider' => 'mpesa', 'reference' => (string) $res['CheckoutRequestID'], 'pending' => true,
            'merchant_request_id' => (string) ($res['MerchantRequestID'] ?? '')];
    }
}
