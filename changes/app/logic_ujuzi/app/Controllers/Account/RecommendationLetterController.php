<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Models\AttachmentApplication;
use App\Services\RecommendationLetterService;

class RecommendationLetterController extends BaseAccountController
{
    public function show(string $applicationId): void
    {
        if (!Authz::isStudent($this->user)) {
            flashError('Recommendation letters are issued to students after attachment is completed.');
            redirect('/account/dashboard');
        }

        $application = AttachmentApplication::findForStudent((int) $applicationId, (int) $this->user['id']);
        if (!$application || ($application['status'] ?? '') !== AttachmentApplication::STATUS_RECOMMENDED) {
            flashError('Your recommendation letter appears once the branch admin marks your attachment completed.');
            redirect('/account/attachment-providers');
        }

        $this->render('account.recommendation-letter.show', [
            'pageTitle' => 'Recommendation letter',
            'activeNav' => 'attachment_providers',
            'payload' => RecommendationLetterService::payload($this->user, $application),
        ]);
    }
}
