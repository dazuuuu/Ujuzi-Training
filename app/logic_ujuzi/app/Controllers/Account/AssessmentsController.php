<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentAssessment;
use App\Services\BranchScope;

/**
 * Attachment assessment sheets sent to the organisation that runs the
 * course: its admin sees every student's, a course branch admin their
 * branch's students'.
 */
class AssessmentsController extends BaseAccountController
{
    public function index(): void
    {
        [$orgId, $studentIds] = $this->scope();
        $sheets = AttachmentAssessment::sharedForOrganisation($orgId, $studentIds);
        $open = (int) Request::query('sheet', 0);
        $this->render('account.assessments.index', [
            'pageTitle' => 'Attachment assessments',
            'activeNav' => 'assessments',
            'sheets' => $sheets,
            'open' => $open ? (array_values(array_filter($sheets, static fn(array $s): bool => (int) $s['id'] === $open))[0] ?? null) : null,
        ]);
    }

    private function scope(): array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        if ($role === 'organisation_admin' && !empty($this->user['organisation_id'])) {
            return [(int) $this->user['organisation_id'], null];
        }
        if ($role === 'course_branch_admin') {
            [$orgId, $studentIds] = BranchScope::courseBranch((int) $this->user['id']);
            if ($orgId) {
                return [$orgId, $studentIds];
            }
        }
        flashError('Assessments are for organisations providing courses and their branch admins.');
        redirect('/account/dashboard');
    }
}
