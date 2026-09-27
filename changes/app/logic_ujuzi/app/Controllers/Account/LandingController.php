<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;

/**
 * /account has no page of its own any more — the portals have no Home item,
 * the dashboard is the start page. Old links and bookmarks land there.
 */
class LandingController extends BaseAccountController
{
    public function index(): void
    {
        redirect(AccountRedirect::home($this->user));
    }
}
