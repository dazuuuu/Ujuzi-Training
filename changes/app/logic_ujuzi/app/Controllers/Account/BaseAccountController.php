<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\UserSession;
use App\Core\Url;
use App\Core\View;
use App\Models\AttachmentApplication;
use App\Models\OrganisationBranch;
use App\Models\OrganisationMembership;

abstract class BaseAccountController
{
    protected array $user;

    private const FORCED_PASSWORD_ALLOWED_PATHS = ['/account/change-password', '/account/logout'];

    public function __construct()
    {
        UserSession::start();
        $this->user = UserSession::require();

        if (!empty($this->user['must_change_password']) && !in_array(Url::currentPath(), self::FORCED_PASSWORD_ALLOWED_PATHS, true)) {
            redirect('/account/change-password');
        }
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, array_merge([
            'pageTitle' => $data['pageTitle'] ?? 'Account',
            'currentUser' => $this->user,
            'canManageUsers' => Authz::canManageUsers($this->user),
            'isOrgAdmin' => Authz::isOrganisationAdmin($this->user),
            'isBranchAdmin' => ($this->user['role_slug'] ?? '') === 'branch_admin',
            'isCourseBranchAdmin' => ($this->user['role_slug'] ?? '') === 'course_branch_admin',
            'isAttachmentProvider' => ($this->user['role_slug'] ?? '') === 'attachment_trainer',
            'isStudent' => Authz::isStudent($this->user),
            'canManageBranches' => Authz::canManageBranches($this->user),
            'canCreateCourses' => Authz::canCreateCourses($this->user),
            'canViewCourses' => Authz::canViewCourses($this->user),
            'pendingRequestCount' => $this->pendingRequestCount(),
        ], $data));
    }

    private function pendingRequestCount(): int
    {
        try {
            $roleSlug = (string) ($this->user['role_slug'] ?? '');
            if ($roleSlug === 'organisation_admin' && !empty($this->user['organisation_id'])) {
                $orgId = (int) $this->user['organisation_id'];
                return count(OrganisationMembership::pendingTrainersForOrganisation($orgId))
                    + count(OrganisationMembership::pendingStudentsForOrganisation($orgId));
            }
            if ($roleSlug === 'branch_admin') {
                $count = 0;
                foreach (OrganisationBranch::forBranchAdmin((int) $this->user['id']) as $branch) {
                    foreach (AttachmentApplication::forBranch((int) $branch['id']) as $application) {
                        if (($application['status'] ?? '') === AttachmentApplication::STATUS_PENDING) {
                            $count++;
                        }
                    }
                }
                return $count;
            }
            if ($roleSlug === 'attachment_trainer') {
                $count = 0;
                foreach (AttachmentApplication::forProvider((int) $this->user['id']) as $application) {
                    if (empty($application['branch_id']) && ($application['status'] ?? '') === AttachmentApplication::STATUS_PENDING) {
                        $count++;
                    }
                }
                return $count;
            }
            if ($roleSlug === 'course_branch_admin') {
                $count = 0;
                foreach (OrganisationBranch::forBranchAdmin((int) $this->user['id']) as $branch) {
                    $count += count(OrganisationMembership::pendingStudentsForBranch((int) $branch['id']));
                }
                return $count;
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return 0;
    }
}
