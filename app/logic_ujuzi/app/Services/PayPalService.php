<?php

namespace App\Services;

use App\Core\Database;
use App\Models\StoreSetting;

/** Buying coins with PayPal (Orders v2): create an order, send the student to PayPal, capture on return. */
class PayPalService
{
    public static function configured(): bool
    {
        return trim((string) StoreSetting::get('paypal_client_id')) !== '' && trim((string) StoreSetting::get('paypal_secret')) !== '';
    }

    private static function base(): string
    {
        return StoreSetting::get('paypal_environment') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private static function rate(): float
    {
        $r = (float) StoreSetting::get('course_ksh_usd_rate', '130');
        return $r > 0 ? $r : 130.0;
    }

    private static function call(string $method, string $path, ?array $json, array $headers, ?string $raw = null): array
    {
        $ch = curl_init(self::base() . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers]);
        if ($json !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        } elseif ($raw !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $raw);
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        }
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false) {
            throw new PaymentException('Could not reach PayPal. Please try again.');
        }
        return json_decode((string) $body, true) ?: [];
    }

    private static function token(): string
    {
        $res = self::call('POST', '/v1/oauth2/token', null, [
            'Authorization: Basic ' . base64_encode(trim((string) StoreSetting::get('paypal_client_id')) . ':' . trim((string) StoreSetting::get('paypal_secret'))),
            'Content-Type: application/x-www-form-urlencoded',
        ], 'grant_type=client_credentials');
        if (empty($res['access_token'])) {
            throw new PaymentException('PayPal rejected the saved keys. Ask the administrator to check them.');
        }
        return (string) $res['access_token'];
    }

    /** Creates the order and returns PayPal's approval URL. */
    public static function start(int $userId, float $amountKsh, string $returnTo, string $returnUrl, string $cancelUrl): string
    {
        $usd = round($amountKsh / self::rate(), 2);
        if ($usd < 0.01) {
            throw new PaymentException('That amount is too small for PayPal.');
        }
        $res = self::call('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [['amount' => ['currency_code' => 'USD', 'value' => number_format($usd, 2, '.', '')], 'description' => 'Wallet top up']],
            'application_context' => ['return_url' => $returnUrl, 'cancel_url' => $cancelUrl, 'user_action' => 'PAY_NOW', 'shipping_preference' => 'NO_SHIPPING'],
        ], ['Content-Type: application/json', 'Authorization: Bearer ' . self::token()]);
        $approve = '';
        foreach ($res['links'] ?? [] as $l) {
            if (($l['rel'] ?? '') === 'approve' || ($l['rel'] ?? '') === 'payer-action') {
                $approve = (string) $l['href'];
            }
        }
        if (empty($res['id']) || $approve === '') {
            throw new PaymentException('PayPal could not start the payment.');
        }
        Database::connection()->prepare('INSERT INTO paypal_payments (user_id, order_id, amount_ksh, amount_usd, return_to) VALUES (?, ?, ?, ?, ?)')
            ->execute([$userId, $res['id'], $amountKsh, $usd, mb_substr($returnTo, 0, 120)]);
        return $approve;
    }

    /** Captures an approved order and credits the wallet once. Returns [amountKsh, returnTo] or throws. */
    public static function capture(int $userId, string $orderId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM paypal_payments WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new PaymentException('That PayPal payment was not found.');
        }
        if ($row['status'] === 'success') {
            return [(float) $row['amount_ksh'], (string) $row['return_to'], true];
        }
        $res = self::call('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', null, ['Content-Type: application/json', 'Authorization: Bearer ' . self::token()]);
        $capture = $res['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (($res['status'] ?? '') !== 'COMPLETED' || !$capture) {
            $pdo->prepare("UPDATE paypal_payments SET status = 'failed' WHERE id = ? AND status = 'pending'")->execute([$row['id']]);
            throw new PaymentException('PayPal did not complete the payment, so nothing was charged.');
        }
        $pdo->beginTransaction();
        $lock = $pdo->prepare("UPDATE paypal_payments SET status = 'success', capture_id = ? WHERE id = ? AND status = 'pending'");
        $lock->execute([(string) ($capture['id'] ?? ''), $row['id']]);
        if ($lock->rowCount() === 1) {
            WalletService::creditDeposit((int) $row['user_id'], (float) $row['amount_ksh'], 'paypal', (string) ($capture['id'] ?? $orderId), 'PayPal');
        }
        $pdo->commit();
        return [(float) $row['amount_ksh'], (string) $row['return_to'], false];
    }
}
