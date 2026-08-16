<?php

namespace App\Controllers\Account;

use App\Core\CustomerSession;
use App\Core\Request;
use App\Core\View;
use App\Models\Customer;
use App\Services\MailerException;
use App\Services\OtpService;

class AuthController
{
    public function __construct()
    {
        CustomerSession::start();
    }

    public function showLogin(): void
    {
        if (CustomerSession::current()) {
            redirect('/account/orders');
        }
        View::render('account.login', ['error' => '', 'method' => 'email', 'old' => []]);
    }

    public function login(): void
    {
        if (CustomerSession::current()) {
            redirect('/account/orders');
        }

        $method = Request::post('method', 'email');
        $error = '';

        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif ($method === 'phone') {
            $phone = Customer::normalizePhone((string) Request::post('phone', ''));
            $customer = $phone ? Customer::findByIdentifier('phone', $phone) : null;
            if ($customer) {
                CustomerSession::login((int) $customer['id']);
                redirect('/account/orders');
            }
            $error = "We couldn't find an account for that phone number. Accounts are created automatically the first time you check out — place an order first, then come back here to track it.";
        } else {
            $email = trim((string) Request::post('email', ''));
            $customer = $email ? Customer::findByIdentifier('email', $email) : null;
            if ($customer) {
                try {
                    OtpService::issueAndSend((int) $customer['id'], $customer['email'], 'login');
                    $_SESSION['pending_customer_id'] = (int) $customer['id'];
                    redirect('/account/verify');
                } catch (MailerException $e) {
                    $error = 'We could not send your login code right now. Please try again shortly, or contact concierge@pentagoncollections.com.';
                } catch (\Throwable $e) {
                    $error = 'Something went wrong. Please try again shortly.';
                }
            } else {
                $error = "We couldn't find an account for that email address. Accounts are created automatically the first time you check out — place an order first, then come back here to track it.";
            }
        }

        View::render('account.login', [
            'error' => $error,
            'method' => $method,
            'old' => ['email' => Request::post('email', ''), 'phone' => Request::post('phone', '')],
        ]);
    }

    public function showVerify(): void
    {
        if (CustomerSession::current()) {
            redirect('/account/orders');
        }
        $pending = $this->pendingCustomer();
        if (!$pending) {
            redirect('/account/login');
        }
        View::render('account.verify', ['error' => '', 'notice' => '', 'email' => $pending['email']]);
    }

    public function verify(): void
    {
        if (CustomerSession::current()) {
            redirect('/account/orders');
        }
        $pending = $this->pendingCustomer();
        if (!$pending) {
            redirect('/account/login');
        }

        $error = '';
        $notice = '';

        if (!csrfVerify(Request::post('csrf_token'))) {
            $error = 'Your session expired. Please try again.';
        } elseif (Request::post('action') === 'resend') {
            try {
                OtpService::issueAndSend((int) $pending['id'], $pending['email'], 'login');
                $notice = 'A new code has been sent to ' . $pending['email'] . '.';
            } catch (MailerException $e) {
                $error = 'We could not resend the code right now. Please try again shortly.';
            } catch (\Throwable $e) {
                $error = 'Something went wrong. Please try again shortly.';
            }
        } else {
            $code = trim((string) Request::post('code', ''));
            if (OtpService::verify((int) $pending['id'], $code, 'login')) {
                Customer::markEmailVerified((int) $pending['id']);
                CustomerSession::login((int) $pending['id']);
                unset($_SESSION['pending_customer_id']);
                redirect('/account/orders');
            }
            $error = 'That code is incorrect or has expired. Please try again or request a new one.';
        }

        View::render('account.verify', ['error' => $error, 'notice' => $notice, 'email' => $pending['email']]);
    }

    public function logout(): void
    {
        CustomerSession::logout();
        redirect('/account/login');
    }

    private function pendingCustomer(): ?array
    {
        if (empty($_SESSION['pending_customer_id'])) {
            return null;
        }
        $customer = Customer::find((int) $_SESSION['pending_customer_id']);
        if (!$customer) {
            unset($_SESSION['pending_customer_id']);
            return null;
        }
        return $customer;
    }
}
