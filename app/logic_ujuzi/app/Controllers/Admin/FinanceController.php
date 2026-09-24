<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Organisation;
use App\Services\PaymentGateway;
use App\Services\WalletService;

class FinanceController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.finance.index', [
            'pageTitle' => 'Finance',
            'activeNav' => 'finance',
            'summary' => WalletService::systemSummary(),
            'organisations' => WalletService::organisationSummaries(),
            'transactions' => WalletService::recentTransactions(),
            'coinsPer100' => WalletService::coinsPer100(),
            'paymentMode' => PaymentGateway::mode(),
        ]);
    }

    public function organisation(string $id): void
    {
        $organisation = Organisation::find((int) $id);
        if (!$organisation) {
            flashError('That organisation was not found.');
            redirect('/admin/finance');
        }
        View::render('admin.finance.organisation', [
            'pageTitle' => $organisation['name'] . ' finances',
            'activeNav' => 'finance',
            'organisation' => $organisation,
            'courses' => WalletService::courseEarnings(null, (int) $organisation['id']),
            'lines' => WalletService::earningLines(null, (int) $organisation['id'], 200),
        ]);
    }

    public function updateRate(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/finance');
        }
        $rate = (float) Request::post('coins_per_100_ksh', 0);
        if ($rate <= 0) {
            flashError('Enter a coin rate above zero.');
            redirect('/admin/finance');
        }
        WalletService::setCoinsPer100($rate);
        flashSuccess('Coin rate updated.');
        redirect('/admin/finance');
    }
}
