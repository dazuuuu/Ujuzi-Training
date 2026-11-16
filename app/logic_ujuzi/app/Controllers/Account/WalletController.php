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
        } elseif ($earner = $this->earnerScope()) {
            [$from, $to] = \App\Services\AttachmentReport::dateRange();
            [$trainerId, $orgId, $studentIds, $scope] = $earner;
            $data = array_replace($data, [
                'mode' => 'earner',
                'scope' => $scope,
                'from' => $from,
                'to' => $to,
                'courses' => WalletService::courseEarnings($trainerId, $orgId, $from, $to, $studentIds),
                'lines' => WalletService::earningLines($trainerId, $orgId, 500, $from, $to, $studentIds),
            ]);
        }

        $this->render('account.wallet.index', $data);
    }

    /** Earnings as Excel or PDF (PDF opens in the browser to view or print), for the chosen dates. */
    public function export(string $format): void
    {
        $earner = $this->earnerScope();
        if (!$earner || !in_array($format, ['xlsx', 'pdf'], true)) {
            redirect('/account/wallet');
        }
        [$from, $to] = \App\Services\AttachmentReport::dateRange();
        [$trainerId, $orgId, $studentIds, $scope] = $earner;
        $courses = WalletService::courseEarnings($trainerId, $orgId, $from, $to, $studentIds);
        $lines = WalletService::earningLines($trainerId, $orgId, 5000, $from, $to, $studentIds);
        $range = ($from ?: 'start') . ' to ' . ($to ?: date('Y-m-d'));

        $headers = ['Course', 'Fee (Ksh)', 'Students', 'Collected (Ksh)', 'Balance due (Ksh)'];
        $rows = [];
        $totals = ['Total', '', 0, 0.0, 0.0];
        foreach ($courses as $c) {
            $expected = (float) $c['fee'] * (int) $c['students'];
            $due = max(0, $expected - (float) $c['collected']);
            $rows[] = [(string) $c['title'], (float) $c['fee'], (int) $c['students'], (float) $c['collected'], $due];
            $totals[2] += (int) $c['students'];
            $totals[3] += (float) $c['collected'];
            $totals[4] += $due;
        }
        $name = 'earnings-' . str_replace(' ', '-', $range);
        if ($format === 'pdf') {
            $paymentRows = array_map(static fn(array $l): array => [
                date('Y-m-d H:i', strtotime((string) $l['created_at'])),
                trim(($l['payer_first'] ?? '') . ' ' . ($l['payer_last'] ?? '')) ?: (string) ($l['payer_email'] ?? ''),
                (string) ($l['payer_registration_number'] ?? ''),
                (string) ($l['course_title'] ?? ''),
                (float) $l['amount_ksh'],
                (string) ($l['reference'] ?? ''),
            ], $lines);
            // One PDF: the per-course summary, then every payment.
            $all = array_merge($rows, [$totals, ['', '', '', '', '']], [['PAYMENTS RECEIVED', '', '', '', '']]);
            foreach ($paymentRows as $p) {
                $all[] = [$p[0] . ' · ' . $p[1] . ($p[2] !== '' ? ' (' . $p[2] . ')' : ''), $p[3], '', $p[4], $p[5]];
            }
            \App\Services\PdfTable::download($name, ($scope === 'tutor' ? 'My earnings' : 'Finances') . ' — ' . userDisplayName($this->user), appName() . ' · ' . $range . ' · generated ' . date('j M Y H:i'), $headers, $all, null, true);
        }
        $rows[] = $totals;
        \App\Services\SpreadsheetExport::download($name, 'Earnings', $headers, $rows);
    }

    /**
     * [trainer id, organisation id, student ids (null = all), scope] for people
     * who receive course money, or null: a tutor (their courses), an
     * organisation admin (all its courses) or a course branch admin (their
     * organisation's courses, for the students of their branches only).
     */
    private function earnerScope(): ?array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        if ($role === 'trainer') {
            return [(int) $this->user['id'], null, null, 'tutor'];
        }
        if ($role === 'organisation_admin' && !empty($this->user['organisation_id'])) {
            return [null, (int) $this->user['organisation_id'], null, 'organisation'];
        }
        if ($role === 'course_branch_admin') {
            [$orgId, $studentIds] = \App\Services\BranchScope::courseBranch((int) $this->user['id']);
            return $orgId ? [null, $orgId, $studentIds, 'branch'] : null;
        }
        return null;
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
            if (!empty($payment['pending'])) {
                flashSuccess('Check your phone: enter your M-Pesa PIN to pay Ksh ' . number_format($amount, 2) . '. Your coins appear in your wallet as soon as the payment is confirmed.');
            } else
            flashSuccess('Deposit of Ksh ' . number_format($amount, 2) . ' received (' . $payment['reference'] . '). You earned ' . rtrim(rtrim(number_format($coins, 2), '0'), '.') . ' coins.');
        } catch (WalletException $e) {
            flashError($e->getMessage());
        }
        $back = (string) Request::post('return_to', '');
        redirect(str_starts_with($back, '/account/courses/') || $back === '/account/dashboard' ? $back : '/account/wallet');
    }

    /** Sends the student to PayPal to pay for coins. */
    public function paypalStart(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'student' || !csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/wallet');
        }
        $amount = round((float) Request::post('amount_ksh', 0), 2);
        $back = (string) Request::post('return_to', '');
        $back = str_starts_with($back, '/account/courses/') || $back === '/account/dashboard' ? $back : '/account/wallet';
        if ($amount < \App\Services\WalletService::MIN_DEPOSIT_KSH) {
            flashError('The minimum deposit is Ksh ' . \App\Services\WalletService::MIN_DEPOSIT_KSH . '.');
            redirect($back);
        }
        if (!\App\Services\PayPalService::configured()) {
            flashError('PayPal is not set up yet.');
            redirect($back);
        }
        try {
            $approve = \App\Services\PayPalService::start((int) $this->user['id'], $amount, $back, \App\Core\Url::absolute('/account/wallet/paypal/return'), \App\Core\Url::absolute('/account/wallet'));
        } catch (\App\Services\PaymentException $e) {
            flashError($e->getMessage());
            redirect($back);
        }
        header('Location: ' . $approve);
        exit;
    }

    /** PayPal sends the student back here; capture the order and credit coins. */
    public function paypalReturn(): void
    {
        $order = (string) Request::query('token', '');
        try {
            [$amount, $back, $already] = \App\Services\PayPalService::capture((int) $this->user['id'], $order);
            if (!$already) {
                $coins = \App\Services\WalletService::coins($amount);
                flashSuccess('PayPal payment received. You earned ' . rtrim(rtrim(number_format($coins, 2), '0'), '.') . ' coins.');
            }
        } catch (\App\Services\PaymentException $e) {
            flashError($e->getMessage());
            $back = '/account/wallet';
        }
        redirect($back ?: '/account/wallet');
    }
}
