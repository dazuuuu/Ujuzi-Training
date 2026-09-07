<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\OrganisationBranch;
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
            'owner_user_id' => $this->branchOwnerType() === 'attachment_provider' ? (int) $this->user['id'] : null,
            'title' => $title,
            'location' => $location,
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
        redirect('/account/branches');
    }

    private function requireBranchManager(): void
    {
        if (!Authz::isOrganisationAdmin($this->user) && ($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisation admins and attachment providers can manage branches.');
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
        if (!$ownsOrganisationBranch && !$ownsProviderBranch) {
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
        return OrganisationBranch::forOrganisation((int) $this->user['organisation_id']);
    }

    private function branchOwnerType(): string
    {
        return ($this->user['role_slug'] ?? '') === 'attachment_trainer' ? 'attachment_provider' : 'organisation';
    }

    private function branchOrganisationId(): ?int
    {
        $orgId = (int) ($this->user['organisation_id'] ?? 0);
        return $orgId > 0 ? $orgId : null;
    }

    private function blankForm(): array
    {
        return [
            'title' => '',
            'location' => '',
            'cover_image' => '',
            'sort_order' => 0,
            'details' => [],
        ];
    }
}
