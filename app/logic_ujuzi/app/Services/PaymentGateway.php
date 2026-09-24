<?php

namespace App\Services;

use App\Models\StoreSetting;

class PaymentException extends \Exception {}

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
    public static function mode(): string
    {
        return StoreSetting::get('wallet_payment_mode', 'simulation') === 'live' ? 'live' : 'simulation';
    }

    /** @return array{provider:string, reference:string} */
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

    private static function chargeLive(string $phone, float $amountKsh): array
    {
        // TODO: Daraja STK push (OAuth token -> /mpesa/stkpush/v1/processrequest), then confirm via callback.
        throw new PaymentException('Live M-Pesa payments are not connected yet.');
    }
}
