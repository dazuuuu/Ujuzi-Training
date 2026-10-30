<?php

namespace App\Controllers\Account;

use App\Core\View;
use App\Services\Receipts;

/** The student's payment history: every receipt, to view, print or save as PDF. */
class PaymentController extends BaseAccountController
{
    public function index(): void
    {
        $this->render('account.payments.index', [
            'pageTitle' => 'Payment history',
            'activeNav' => 'payments',
            'receipts' => Receipts::forUser((int) $this->user['id']),
        ]);
    }

    public function show(string $id): void
    {
        $receipt = Receipts::find((int) $id, (int) $this->user['id']);
        if (!$receipt) {
            flashError('Receipt not found.');
            redirect('/account/payments');
        }
        View::render('account.payments.receipt', ['receipt' => $receipt]);
    }
}
