<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Services\WalletException;
use App\Services\WalletService;

class WalletController extends BaseAccountController
{
    public function index(): void
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        $userId = (int) $this->user['id'];
        $data = ['pageTitle' => 'Wallet', 'activeNav' => 'wallet', 'mode' => 'none'];

        // array_replace, not +=: the union operator keeps the 'mode' => 'none' above.
        if ($role === 'student') {
            $data = array_replace($data, [
                'mode' => 'student',
                'balanceKsh' => WalletService::balanceKsh($userId),
                'depositedKsh' => WalletService::totalsFor($userId, 'deposit'),
                'spentKsh' => WalletService::totalsFor($userId, 'course_payment'),
                'courses' => WalletService::studentCourses($userId),
                'transactions' => WalletService::transactionsFor($userId),
            ]);
        } elseif ($role === 'trainer') {
            $data = array_replace($data, ['mode' => 'earner', 'scope' => 'tutor', 'courses' => WalletService::courseEarnings($userId, null), 'lines' => WalletService::earningLines($userId, null)]);
        } elseif ($role === 'organisation_admin' && !empty($this->user['organisation_id'])) {
            $orgId = (int) $this->user['organisation_id'];
            $data = array_replace($data, ['mode' => 'earner', 'scope' => 'organisation', 'courses' => WalletService::courseEarnings(null, $orgId), 'lines' => WalletService::earningLines(null, $orgId)]);
        }

        $this->render('account.wallet.index', $data);
    }

    public function deposit(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'student') {
            flashError('Only students deposit into a wallet.');
            redirect('/account/wallet');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/wallet');
        }
        $amount = (float) Request::post('amount_ksh', 0);
        try {
            $payment = WalletService::deposit((int) $this->user['id'], $amount, (string) Request::post('phone', ''));
            $coins = WalletService::coins($amount);
            flashSuccess('Deposit of Ksh ' . number_format($amount, 2) . ' received (' . $payment['reference'] . '). You earned ' . rtrim(rtrim(number_format($coins, 2), '0'), '.') . ' coins.');
        } catch (WalletException $e) {
            flashError($e->getMessage());
        }
        $back = (string) Request::post('return_to', '');
        redirect(str_starts_with($back, '/account/courses/') ? $back : '/account/wallet');
    }
}
