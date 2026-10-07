<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\StudentLookup;

/** Super Admin looks a student up by registration number. */
class StudentLookupController extends BaseAdminController
{
    public function index(): void
    {
        $number = strtoupper(trim((string) Request::query('reg', '')));
        View::render('admin.student-lookup.index', [
            'pageTitle' => 'Student lookup',
            'activeNav' => 'student-lookup',
            'number' => $number,
            'record' => $number !== '' ? StudentLookup::find($number) : null,
            'formAction' => '/admin/student-lookup',
        ]);
    }
}
