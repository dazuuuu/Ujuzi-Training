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
        $this->requireOrgAdmin();
        $this->render('account.branches.index', [
            'pageTitle' => 'Branches',
            'activeNav' => 'branches',
            'branches' => OrganisationBranch::forOrganisation((int) $this->user['organisation_id']),
        ]);
    }

    public function create(): void
    {
        $this->requireOrgAdmin();
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
        $this->requireOrgAdmin();
        $this->persist(null);
    }

    public function edit(string $id): void
    {
        $this->requireOrgAdmin();
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
        $this->requireOrgAdmin();
        $this->ownedBranch((int) $id);
        $this->persist((int) $id);
    }

    public function destroy(string $id): void
    {
        $this->requireOrgAdmin();
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
            'organisation_id' => (int) $this->user['organisation_id'],
            'title' => $title,
            'location' => $location,
            'cover_image' => $cover,
            'sort_order' => $sortOrder,
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

    private function requireOrgAdmin(): void
    {
        if (!Authz::isOrganisationAdmin($this->user)) {
            flashError('Only organisation admins can manage branches.');
            redirect('/account/dashboard');
        }
    }

    private function ownedBranch(int $id): array
    {
        $branch = OrganisationBranch::find($id);
        if (!$branch || (int) $branch['organisation_id'] !== (int) $this->user['organisation_id']) {
            flashError('That branch could not be found.');
            redirect('/account/branches');
        }
        return $branch;
    }

    private function blankForm(): array
    {
        return [
            'title' => '',
            'location' => '',
            'cover_image' => '',
            'sort_order' => 0,
        ];
    }
}
