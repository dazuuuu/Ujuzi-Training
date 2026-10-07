<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentAudience;
use App\Models\Organisation;
use App\Models\OrganisationCategory;

/**
 * Lets an organisation providing attachment ask to take the students of
 * organisations providing courses, per category. Each organisation approves
 * or declines; students see the provider once approved.
 */
class AttachmentAudienceController extends BaseAccountController
{
    public function edit(): void
    {
        $this->requireProvider();

        $organisations = [];
        foreach (Organisation::providingCourses() as $organisation) {
            $categories = OrganisationCategory::forOrganisation((int) $organisation['id'], true);
            if ($categories) {
                $organisation['categories'] = $categories;
                $organisations[] = $organisation;
            }
        }

        $this->render('account.attachment-audience.index', [
            'pageTitle' => 'Where you appear',
            'activeNav' => 'attachment_audience',
            'organisations' => $organisations,
            'selectedCategoryIds' => AttachmentAudience::categoryIdsFor((int) $this->user['id']),
            'statuses' => AttachmentAudience::statusesFor((int) $this->user['id']),
        ]);
    }

    public function update(): void
    {
        $this->requireProvider();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/attachment-audience');
        }

        $ids = Request::post('category_ids', []);
        $saved = AttachmentAudience::sync((int) $this->user['id'], is_array($ids) ? $ids : []);
        $saved > 0
            ? flashSuccess('Saved. Each organisation providing courses reviews new categories; its students see you once it approves.')
            : flashError('Saved with nothing selected — no student can see you until you ask for at least one category.');
        redirect('/account/attachment-audience');
    }

    private function requireProvider(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisations providing attachment choose where they appear.');
            redirect('/account/dashboard');
        }
    }
}
