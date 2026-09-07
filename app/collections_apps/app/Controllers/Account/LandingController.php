<?php

namespace App\Controllers\Account;

class LandingController extends BaseAccountController
{
    public function index(): void
    {
        $this->render('account.landing', [
            'pageTitle' => 'Welcome',
            'activeNav' => 'landing',
        ]);
    }
}
