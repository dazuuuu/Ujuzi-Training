<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentAssessment;

/**
 * The organisation providing attachment writes its own assessment form once;
 * its branch admins answer it for every attachee.
 */
class AssessmentTemplateController extends BaseAccountController
{
    public function index(): void
    {
        $this->providerOnly();
        $this->render('account.assessment-template.index', [
            'pageTitle' => 'Assessment form',
            'activeNav' => 'assessment_template',
            'questions' => AttachmentAssessment::template((int) $this->user['id']),
        ]);
    }

    public function update(): void
    {
        $this->providerOnly();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/assessment-template');
        }
        $saved = AttachmentAssessment::saveTemplate((int) $this->user['id'], (array) Request::post('questions', []));
        flashSuccess(count($saved) . ' question' . (count($saved) === 1 ? '' : 's') . ' saved. Your branch admins now fill this form for every attachee.');
        redirect('/account/assessment-template');
    }

    private function providerOnly(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only the organisation providing attachment sets its assessment form.');
            redirect('/account/dashboard');
        }
    }
}
