<?php

namespace App\Controllers\Admin;

use App\Core\ErrorLog;
use App\Core\Request;
use App\Core\View;

/** Main Super Admins read the error log (the real cause behind "Something went wrong"). */
class ErrorLogController extends BaseAdminController
{
    public function index(): void
    {
        $lines = max(50, min(5000, (int) Request::query('lines', 400)));
        View::render('admin.error-logs.index', [
            'pageTitle' => 'Error logs',
            'activeNav' => 'error-logs',
            'log' => ErrorLog::tail($lines),
            'lines' => $lines,
            'size' => ErrorLog::size(),
            'path' => ErrorLog::path(),
        ]);
    }

    public function clear(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/error-logs');
        }
        ErrorLog::clear();
        flashSuccess('Error log cleared.');
        redirect('/admin/error-logs');
    }
}
