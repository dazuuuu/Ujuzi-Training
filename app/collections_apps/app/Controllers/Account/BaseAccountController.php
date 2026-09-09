<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\UserSession;
use App\Core\View;

abstract class BaseAccountController
{
    protected array $user;

    public function __construct()
    {
        UserSession::start();
        $this->user = UserSession::require();
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, array_merge([
            'pageTitle' => $data['pageTitle'] ?? 'Account',
            'currentUser' => $this->user,
            'canManageUsers' => Authz::canManageUsers($this->user),
            'isOrgAdmin' => Authz::isOrganisationAdmin($this->user),
            'canManageBranches' => true,
            'canCreateCourses' => Authz::canCreateCourses($this->user),
            'canViewCourses' => Authz::canViewCourses($this->user),
        ], $data));
    }
}
