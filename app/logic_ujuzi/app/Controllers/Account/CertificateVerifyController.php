<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Services\StudentLookup;

/**
 * Organisations (courses and attachment) and their branch admins look a
 * student up by the registration number on their certificate: full details,
 * photo, courses, fees, certificates and attachments.
 */
class CertificateVerifyController extends BaseAccountController
{
    private const ROLES = ['organisation_admin', 'course_branch_admin', 'attachment_trainer', 'branch_admin'];

    public function index(): void
    {
        if (!in_array($this->user['role_slug'] ?? '', self::ROLES, true)) {
            flashError('Only organisations and their branch admins look up students.');
            redirect('/account/dashboard');
        }

        $number = strtoupper(trim((string) Request::query('reg', '')));
        $this->render('account.certificate.verify', [
            'pageTitle' => 'Student lookup',
            'activeNav' => 'verify_certificate',
            'number' => $number,
            'record' => $number !== '' ? StudentLookup::find($number) : null,
            'formAction' => '/account/student-lookup',
        ]);
    }
}
