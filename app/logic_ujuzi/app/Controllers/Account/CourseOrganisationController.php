<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\Organisation;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;

class CourseOrganisationController extends BaseAccountController
{
    private function allowed(): bool
    {
        return Authz::isStudent($this->user);
    }

    public function index(): void
    {
        if (!$this->allowed()) {
            flashError('Only students request an organisation providing courses.');
            redirect('/account/dashboard');
        }

        $organisations = Organisation::providingCourses();
        $branchesByOrg = [];
        $categoriesByOrg = [];
        foreach ($organisations as $organisation) {
            $orgId = (int) $organisation['id'];
            $branchesByOrg[$orgId] = OrganisationBranch::forOrganisation($orgId);
            $categoriesByOrg[$orgId] = OrganisationCategory::forOrganisation($orgId, true);
        }

        $memberships = [];
        foreach (OrganisationMembership::forUser((int) $this->user['id']) as $membership) {
            $memberships[(int) $membership['organisation_id']] = $membership;
        }

        $this->render('account.course-organisations.index', [
            'pageTitle' => 'Organisations',
            'activeNav' => 'course_organisations',
            'organisations' => $organisations,
            'branchesByOrg' => $branchesByOrg,
            'categoriesByOrg' => $categoriesByOrg,
            'memberships' => $memberships,
        ]);
    }

    public function request(): void
    {
        if (!$this->allowed()) {
            flashError('Only students request an organisation providing courses.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/course-organisations');
        }

        $organisationId = (int) Request::post('organisation_id', 0);
        $organisations = Organisation::providingCourses();
        $organisation = null;
        foreach ($organisations as $candidate) {
            if ((int) $candidate['id'] === $organisationId) {
                $organisation = $candidate;
                break;
            }
        }
        if (!$organisation) {
            flashError('Choose an organisation from the list.');
            redirect('/account/course-organisations');
        }

        $branchId = (int) Request::post('branch_id', 0);
        $branches = OrganisationBranch::forOrganisation($organisationId);
        $allowedBranchIds = array_map(static fn(array $branch): int => (int) ($branch['id'] ?? 0), $branches);
        if ($branchId > 0 && !in_array($branchId, $allowedBranchIds, true)) {
            flashError('Choose a branch that belongs to that organisation.');
            redirect('/account/course-organisations');
        }

        // Once the organisation has approved the student WITH categories
        // already chosen, only the organisation can change them from here on
        // — but a first-time pick (including auto-approved org-created
        // students who haven't chosen yet) is still allowed.
        $existingMembership = null;
        foreach (OrganisationMembership::forUser((int) $this->user['id']) as $membership) {
            if ((int) $membership['organisation_id'] === $organisationId) {
                $existingMembership = $membership;
                break;
            }
        }
        if ($existingMembership && $existingMembership['status'] === OrganisationMembership::STATUS_APPROVED && !empty($existingMembership['category_ids'])) {
            flashError('Your categories are already approved. Ask the organisation to change them.');
            redirect('/account/course-organisations');
        }

        $postedCategoryIds = Request::post('category_ids', []);
        $postedCategoryIds = is_array($postedCategoryIds) ? array_map('intval', $postedCategoryIds) : [];
        $categories = OrganisationCategory::forOrganisation($organisationId, true);
        $allowedCategoryIds = array_map(static fn(array $cat): int => (int) ($cat['id'] ?? 0), $categories);
        $categoryIds = array_values(array_intersect($postedCategoryIds, $allowedCategoryIds));
        if (!$categoryIds) {
            flashError('Choose at least one category you\'re interested in.');
            redirect('/account/course-organisations');
        }

        OrganisationMembership::requestOrganisation((int) $this->user['id'], $organisationId, $branchId > 0 ? $branchId : null, $categoryIds);
        flashSuccess('Request sent. The organisation must approve you before you can see their courses.');
        redirect('/account/course-organisations');
    }
}
