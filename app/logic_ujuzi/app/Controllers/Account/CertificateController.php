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

        // Every course has its own certificate. Without ?course the student
        // sees the list of their certificates, one per finished course.
        $only = (int) \App\Core\Request::query('course', 0);
        $course = null;
        foreach ($completed as $candidate) {
            if ((int) $candidate['id'] === $only) {
                $course = $candidate;
                break;
            }
        }
        if (!$course && count($completed) === 1) {
            $course = $completed[0];
        }

        $this->render('account.certificate.show', [
            'pageTitle' => $course ? $course['title'] : 'My Certificates',
            'activeNav' => 'certificate',
            'payload' => $course ? CertificateService::payload($this->user, [$course]) : null,
            'completedCourses' => $completed,
            'singleCourse' => $course,
        ]);
    }
}
