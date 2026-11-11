<?php

namespace App\Controllers;

use App\Services\WalletService;

/** Receives Safaricom's STK push result. Public by nature; only pending requests we created can be settled. */
class MpesaCallbackController
{
    public function handle(): void
    {
        $body = json_decode((string) file_get_contents('php://input'), true);
        try {
            if (is_array($body)) {
                WalletService::settleMpesa($body);
            }
        } catch (\Throwable $e) {
            error_log('M-Pesa callback: ' . $e->getMessage());
        }
        header('Content-Type: application/json');
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }
}
