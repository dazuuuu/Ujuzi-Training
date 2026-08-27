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

        $completed = Course::completedByLearner($this->user);
        if (!$completed) {
            flashError('Finish at least one course to open your certificate.');
            redirect('/account/dashboard');
        }

        $this->render('account.certificate.show', [
            'pageTitle' => 'Certificate',
            'activeNav' => 'courses',
            'payload' => CertificateService::payload($this->user, $completed),
            'completedCourses' => $completed,
        ]);
    }
}
