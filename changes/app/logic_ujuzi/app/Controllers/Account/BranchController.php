<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\LoginRoles;
use App\Core\Request;
use App\Core\Url;
use App\Models\OrganisationBranch;
use App\Models\Role;
use App\Models\User;
use App\Services\MailerException;
use App\Services\MailerService;
use App\Services\UploadException;
use App\Services\UploadService;

class BranchController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireBranchManager();
        $this->render('account.branches.index', [
            'pageTitle' => 'Branches',
            'activeNav' => 'branches',
            'branches' => $this->branchesForCurrentUser(),
        ]);
    }

    public function create(): void
    {
        $this->requireBranchManager();
        $this->render('account.branches.form', [
            'pageTitle' => 'Add branch',
            'activeNav' => 'branches',
            'branch' => null,
            'errors' => [],
            'form' => $this->blankForm(),
        ]);
    }

    public function store(): void
    {
        $this->requireBranchManager();
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $this->requireBranchManager();
        $branch = $this->ownedBranch((int) $id);
        $this->render('account.branches.form', [
            'pageTitle' => 'Edit branch',
            'activeNav' => 'branches',
            'branch' => $branch,
            'errors' => [],
            'form' => $branch,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireBranchManager();
        $this->ownedBranch((int) $id);
        $this->persist((int) $id);
    }

    public function destroy(string $id): void
    {
        $this->requireBranchManager();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/branches');
        }
        $branch = $this->ownedBranch((int) $id);
        OrganisationBranch::delete((int) $branch['id']);
        flashSuccess('Branch deleted.');
        redirect('/account/branches');
    }

    private function persist(?int $id): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect($id ? '/account/branches/' . $id . '/edit' : '/account/branches/create');
        }

        $title = trim((string) Request::post('title', ''));
        $location = trim((string) Request::post('location', ''));
        $branchAdminName = trim((string) Request::post('branch_admin_name', ''));
        $branchAdminEmail = strtolower(trim((string) Request::post('branch_admin_email', '')));
        $branchAdminPhone = trim((string) Request::post('branch_admin_phone', ''));
        $sortOrder = (int) Request::post('sort_order', 0);
        $labels = Request::post('details_labels', []);
        $values = Request::post('details_values', []);
        if (!is_array($labels)) {
            $labels = [];
        }
        if (!is_array($values)) {
            $values = [];
        }
        $details = [];
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            $value = trim((string) ($values[$index] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $details[$label] = $value;
        }
        $errors = [];
        if ($title === '') {
            $errors[] = 'Branch title is required.';
        }
        if ($location === '') {
            $errors[] = 'Location is required.';
        }
        $branchAdminUserId = null;
        $branchAdminCreated = false;
        if ($branchAdminEmail !== '') {
            if (!filter_var($branchAdminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid branch admin email.';
            } else {
                $branchAdminUser = User::findByIdentifier('email', $branchAdminEmail);
                $branchAdminRoleSlug = $this->branchOwnerType() === 'organisation' ? 'course_branch_admin' : 'branch_admin';
                $branchAdminRole = Role::findBySlug($branchAdminRoleSlug);
                if ($branchAdminUser) {
                    $branchAdminUserId = (int) $branchAdminUser['id'];
                    $existingSlug = (string) ($branchAdminUser['role_slug'] ?? '');
                    // An account already used as the OTHER kind of branch admin (or a
                    // plain student) gets switched to the role matching THIS branch —
                    // otherwise they'd keep landing in the wrong portal after login.
                    $promotable = ['student', 'branch_admin', 'course_branch_admin'];
                    if ($existingSlug !== $branchAdminRoleSlug && in_array($existingSlug, $promotable, true) && $branchAdminRole) {
                        User::update($branchAdminUserId, array_merge($branchAdminUser, [
                            'role_id' => (int) $branchAdminRole['id'],
                        ]));
                    }
                } elseif (!$branchAdminRole) {
                    $errors[] = 'The branch admin role is not set up yet. Run pending updates first.';
                } else {
                    [$firstName, $lastName] = array_pad(explode(' ', $branchAdminName, 2), 2, '');
                    $branchAdminUserId = User::create([
                        'role_id' => (int) $branchAdminRole['id'],
                        'organisation_id' => null,
                        'email' => $branchAdminEmail,
                        'phone' => $branchAdminPhone,
                        'first_name' => $firstName !== '' ? $firstName : 'Branch',
                        'last_name' => $lastName !== '' ? $lastName : 'Admin',
                        'password' => User::DEFAULT_PASSWORD,
                        'must_change_password' => 1,
                        'is_active' => 1,
                        'email_verified_at' => date('Y-m-d H:i:s'),
                    ]);
                    $branchAdminCreated = true;
                }
            }
        }

        $cover = $id ? (OrganisationBranch::find($id)['cover_image'] ?? null) : null;
        $file = Request::file('cover_image');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $cover = UploadService::store($file, 'org-branches');
            } catch (UploadException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $form = [
            'title' => $title,
            'location' => $location,
            'branch_admin_user_id' => $branchAdminUserId,
            'branch_admin_name' => $branchAdminName,
            'branch_admin_email' => $branchAdminEmail,
            'branch_admin_phone' => $branchAdminPhone,
            'cover_image' => $cover,
            'sort_order' => $sortOrder,
            'details' => $details,
        ];

        if ($errors) {
            $this->render('account.branches.form', [
                'pageTitle' => $id ? 'Edit branch' : 'Add branch',
                'activeNav' => 'branches',
                'branch' => $id ? OrganisationBranch::find($id) : null,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $payload = [
            'organisation_id' => $this->branchOrganisationId(),
            'owner_type' => $this->branchOwnerType(),
            'owner_user_id' => $this->branchOwnerType() === 'organisation' ? null : (int) $this->user['id'],
            'title' => $title,
            'location' => $location,
            'branch_admin_user_id' => $branchAdminUserId,
            'branch_admin_name' => $branchAdminName !== '' ? $branchAdminName : null,
            'branch_admin_email' => $branchAdminEmail !== '' ? $branchAdminEmail : null,
            'branch_admin_phone' => $branchAdminPhone !== '' ? $branchAdminPhone : null,
            'cover_image' => $cover,
            'sort_order' => $sortOrder,
            'details' => $details,
        ];

        if ($id) {
            OrganisationBranch::update($id, $payload);
            flashSuccess('Branch saved.');
        } else {
            OrganisationBranch::create($payload);
            flashSuccess('Branch added.');
        }

        if ($branchAdminCreated && $branchAdminUserId) {
            $this->notifyBranchAdminCreated($branchAdminEmail);
        }
        redirect('/account/branches');
    }

    private function notifyBranchAdminCreated(string $email): void
    {
        $isCourseBranch = $this->branchOwnerType() === 'organisation';
        $roleSlug = $isCourseBranch ? 'course_branch_admin' : 'branch_admin';
        $roleLabel = $isCourseBranch ? 'course branch admin' : 'attachment branch admin';
        $loginUrl = Url::absolute(LoginRoles::loginPath($roleSlug));
        $whatsappText = "Your Branch Admin login:\nEmail: {$email}\nPassword: " . User::DEFAULT_PASSWORD . "\nSign in: {$loginUrl}\nYou'll be asked to set your own password on first login.";
        $whatsappUrl = whatsappShareUrl($whatsappText);

        $emailed = false;
        try {
            MailerService::sendAccountCreated($email, $loginUrl, $roleLabel, User::DEFAULT_PASSWORD);
            $emailed = true;
        } catch (MailerException $e) {
            $emailed = false;
        }

        $message = $emailed
            ? 'Branch admin created. Their login details were emailed to them.'
            : 'Branch admin created. Give them their sign-in and this password: ' . User::DEFAULT_PASSWORD . ' — they will be asked to set their own on first login.';
        if ($whatsappUrl) {
            $message .= ' <a href="' . e($whatsappUrl) . '" target="_blank" rel="noopener" class="underline font-bold">Share via WhatsApp</a>';
        }
        flashSuccessHtml($message);
    }

    private function requireBranchManager(): void
    {
        if (!Authz::canManageBranches($this->user)) {
            flashError('Only organisation admins and attachment providers manage branches.');
            redirect('/account/dashboard');
        }
    }

    private function ownedBranch(int $id): array
    {
        $branch = OrganisationBranch::find($id);
        if (!$branch) {
            flashError('That branch could not be found.');
            redirect('/account/branches');
        }
        $ownsOrganisationBranch = Authz::isOrganisationAdmin($this->user)
            && (int) ($branch['organisation_id'] ?? 0) === (int) $this->user['organisation_id']
            && ($branch['owner_type'] ?? 'organisation') === 'organisation';
        $ownsProviderBranch = ($this->user['role_slug'] ?? '') === 'attachment_trainer'
            && ($branch['owner_type'] ?? '') === 'attachment_provider'
            && (int) ($branch['owner_user_id'] ?? 0) === (int) $this->user['id'];
        $ownsPersonalBranch = !Authz::isOrganisationAdmin($this->user)
            && ($this->user['role_slug'] ?? '') !== 'attachment_trainer'
            && ($branch['owner_type'] ?? '') === 'user'
            && (int) ($branch['owner_user_id'] ?? 0) === (int) $this->user['id'];
        if (!$ownsOrganisationBranch && !$ownsProviderBranch && !$ownsPersonalBranch) {
            flashError('That branch could not be found.');
            redirect('/account/branches');
        }
        return $branch;
    }

    private function branchesForCurrentUser(): array
    {
        if (($this->user['role_slug'] ?? '') === 'attachment_trainer') {
            return OrganisationBranch::forAttachmentProvider((int) $this->user['id']);
        }
        if (Authz::isOrganisationAdmin($this->user)) {
            return OrganisationBranch::forOrganisation((int) $this->user['organisation_id']);
        }
        return OrganisationBranch::forUser((int) $this->user['id']);
    }

    private function branchOwnerType(): string
    {
        if (($this->user['role_slug'] ?? '') === 'attachment_trainer') {
            return 'attachment_provider';
        }
        return Authz::isOrganisationAdmin($this->user) ? 'organisation' : 'user';
    }

    private function branchOrganisationId(): ?int
    {
        if ($this->branchOwnerType() !== 'organisation') {
            return null;
        }
        $orgId = (int) ($this->user['organisation_id'] ?? 0);
        return $orgId > 0 ? $orgId : null;
    }

    private function blankForm(): array
    {
        return [
            'title' => '',
            'location' => '',
            'branch_admin_user_id' => null,
            'branch_admin_name' => '',
            'branch_admin_email' => '',
            'branch_admin_phone' => '',
            'cover_image' => '',
            'sort_order' => 0,
            'details' => [],
        ];
    }
}
