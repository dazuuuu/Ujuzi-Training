<?php

namespace App\Controllers\Account;

use App\Core\AccountRedirect;
use App\Core\Authz;
use App\Core\Url;
use App\Core\UserSession;
use App\Core\View;

abstract class BaseAccountController
{
    protected array $user;

    public function __construct()
    {
        UserSession::start();
        $this->user = UserSession::require();
        if (AccountRedirect::needsProfile($this->user) && Url::currentPath() !== '/account/profile') {
            flashSuccess('Complete the registration form assigned to your role first. Your dashboard opens after that.');
            redirect('/account/profile');
        }
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, array_merge([
            'pageTitle' => $data['pageTitle'] ?? 'Account',
            'currentUser' => $this->user,
            'canManageUsers' => Authz::canManageUsers($this->user),
            'isOrgAdmin' => Authz::isOrganisationAdmin($this->user),
            'canManageBranches' => Authz::isOrganisationAdmin($this->user) || ($this->user['role_slug'] ?? '') === 'attachment_trainer',
            'canCreateCourses' => Authz::canCreateCourses($this->user),
            'canViewCourses' => Authz::canViewCourses($this->user),
        ], $data));
    }
}
