<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Models\Course;
use App\Services\CertificateService;

class CertificateController extends BaseAccountController
{
    public function show(): void
    {
        if (!Authz::isStudent($this->user)) {
            flashError('Certificates are issued to students after they finish a course.');
            redirect('/account/dashboard');
        }

        $completed = Course::certifiableCompletedByLearner($this->user);
        if (!$completed) {
            flashError('Finish at least one certificate-enabled course and clear its fee in full. Courses with attachment require provider recommendation before the certificate opens.');
            redirect('/account/dashboard');
        }

        $this->render('account.certificate.show', [
            'pageTitle' => 'Certificate',
            'activeNav' => 'certificate',
            'payload' => CertificateService::payload($this->user, $completed),
            'completedCourses' => $completed,
        ]);
    }
}
