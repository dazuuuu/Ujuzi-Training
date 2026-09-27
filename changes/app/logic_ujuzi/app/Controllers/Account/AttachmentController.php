<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Request;
use App\Models\AttachmentApplication;
use App\Models\AttachmentAudience;
use App\Models\AttachmentMessage;
use App\Models\CourseEnrollment;
use App\Models\OrganisationBranch;
use App\Models\User;
use App\Services\WalletService;

class AttachmentController extends BaseAccountController
{
    public function index(): void
    {
        $this->requireStudent();

        $isEnrolled = $this->isEnrolledForAnyCourse();
        $applications = AttachmentApplication::allForStudent((int) $this->user['id']);
        $taken = $this->takenCategories($applications);

        // A provider is greyed out once every category it shares with the
        // student already has a request.
        $providers = $isEnrolled ? $this->visibleProviders($this->studentCategories()) : [];
        foreach ($providers as &$provider) {
            $provider['open_category_ids'] = array_values(array_diff(array_keys($provider['matching_categories']), array_keys($taken)));
        }
        unset($provider);

        $applicationIds = array_column($applications, 'id');
        $unread = [];
        $latest = [];
        try {
            $unread = AttachmentMessage::unreadCounts($applicationIds, AttachmentMessage::SIDE_STUDENT);
            $latest = AttachmentMessage::latestFor($applicationIds);
        } catch (\Throwable $e) {
            // Notes are optional on this page.
        }

        $this->render('account.attachment-providers.index', [
            'unreadNotes' => $unread,
            'latestNotes' => $latest,
            'pageTitle' => 'Attachment',
            'activeNav' => 'attachment_providers',
            // Attachment opens once the student has enrolled for a course.
            'isEnrolled' => $isEnrolled,
            'feeStanding' => $this->feeStanding(),
            'applications' => $applications,
            'takenCategories' => $taken,
            'providers' => $providers,
        ]);
    }

    /** One provider's page: its details and branches, each branch with a request form. */
    public function show(string $id): void
    {
        $this->requireStudent();
        if (!$this->isEnrolledForAnyCourse()) {
            flashError('Enroll for a course first — attachment opens once you are enrolled.');
            redirect('/account/attachment-providers');
        }

        $provider = $this->visibleProvider((int) $id);
        $applications = AttachmentApplication::allForStudent((int) $this->user['id']);

        $this->render('account.attachment-providers.show', [
            'pageTitle' => $provider['organisation_name'] ?: userDisplayName($provider),
            'activeNav' => 'attachment_providers',
            'provider' => $provider,
            'takenCategories' => $this->takenCategories($applications),
            'chosenBranch' => $this->chosenBranchAt((int) $provider['id'], $applications),
            'feeStanding' => $this->feeStanding(),
        ]);
    }

    public function select(): void
    {
        $this->requireStudent();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/attachment-providers');
        }
        if (!$this->isEnrolledForAnyCourse()) {
            flashError('Enroll for a course first — attachment opens once you are enrolled.');
            redirect('/account/courses');
        }

        $standing = $this->feeStanding();
        if ($standing['below_minimum']) {
            flashError('You have paid ' . (int) floor($standing['paid_ratio'] * 100) . '% of your course fees. Pay at least ' . WalletService::minPaymentPercent() . '% before sending an attachment request.');
            redirect('/account/attachment-providers');
        }

        $providerId = (int) Request::post('provider_id', 0);
        $branchId = (int) Request::post('branch_id', 0);
        $categoryId = (int) Request::post('category_id', 0);
        $provider = $this->visibleProvider($providerId);
        $back = '/account/attachment-providers/' . $providerId;

        // The request is filed under one of the categories this provider
        // targets and the student is approved for.
        $category = $provider['matching_categories'][$categoryId] ?? null;
        if (!$category) {
            flashError('Choose the course category this attachment is for.');
            redirect($back);
        }

        // Only organisations with branches ask for one; otherwise the
        // organisation's own admin reviews the request.
        if ($provider['attachment_branches']) {
            $allowedBranchIds = array_map(
                static fn(array $branch): int => (int) ($branch['id'] ?? 0),
                $provider['attachment_branches']
            );
            if ($branchId < 1 || !in_array($branchId, $allowedBranchIds, true) || !OrganisationBranch::findActive($branchId)) {
                flashError('Choose the branch you want to go to — its admin reviews your request.');
                redirect($back);
            }
        } else {
            $branchId = 0;
        }

        $applications = AttachmentApplication::allForStudent((int) $this->user['id']);
        $taken = $this->takenCategories($applications)[$categoryId] ?? null;
        if ($taken) {
            flashError('You already sent your ' . $category['name'] . ' request to '
                . ($taken['organisation_name'] ?: 'another organisation')
                . '. You can make one attachment request per course category.');
            redirect($back);
        }
        // One branch per provider: every request to this provider goes to the same branch.
        $chosen = $this->chosenBranchAt($providerId, $applications);
        if ($chosen !== null && $chosen !== $branchId) {
            flashError('You already chose a branch at this organisation. Send your request to that same branch.');
            redirect($back);
        }

        if (!AttachmentApplication::saveCategorySelection((int) $this->user['id'], $providerId, $branchId > 0 ? $branchId : null, $categoryId)) {
            flashError('You already have a ' . $category['name'] . ' request. You can make one attachment request per course category.');
            redirect($back);
        }

        flashSuccess(($branchId > 0 ? 'Request sent. The branch admin will review and accept you' : 'Request sent. The organisation will review and accept you')
            . ' for ' . $category['name'] . '.');
        redirect('/account/attachment-providers');
    }

    /** The student's view of one request: its status and the notes with the reviewer. */
    public function thread(string $id): void
    {
        $this->requireStudent();
        $application = AttachmentApplication::findForStudent((int) $id, (int) $this->user['id']);
        if (!$application) {
            flashError('That attachment request could not be found.');
            redirect('/account/attachment-providers');
        }
        AttachmentMessage::markRead($application['id'], AttachmentMessage::SIDE_STUDENT);
        $this->render('account.attachment-providers.thread', [
            'pageTitle' => 'Attachment request',
            'activeNav' => 'attachment_providers',
            'application' => $application,
            'messages' => AttachmentMessage::forApplication($application['id']),
        ]);
    }

    /** The student answers the reviewer, e.g. confirming they meet the requirements. */
    public function reply(string $id): void
    {
        $this->requireStudent();
        $application = AttachmentApplication::findForStudent((int) $id, (int) $this->user['id']);
        if (!$application) {
            flashError('That attachment request could not be found.');
            redirect('/account/attachment-providers');
        }
        $here = '/account/attachments/' . $application['id'];
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($here);
        }
        if ($application['status'] === AttachmentApplication::STATUS_REJECTED) {
            flashError('This request was declined, so it can no longer take replies. You can send a new request.');
            redirect($here);
        }
        $body = trim((string) Request::post('body', ''));
        if ($body === '' || mb_strlen($body) > AttachmentMessage::MAX_LENGTH) {
            flashError($body === '' ? 'Write your reply first.' : 'Keep your reply under ' . AttachmentMessage::MAX_LENGTH . ' characters.');
            redirect($here);
        }
        AttachmentMessage::add($application['id'], (int) $this->user['id'], AttachmentMessage::SIDE_STUDENT, $body);
        flashSuccess('Reply sent.');
        redirect($here);
    }

    /** The student withdraws a request nobody has accepted yet, which frees that category again. */
    public function cancel(string $id): void
    {
        $this->requireStudent();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/attachment-providers');
        }
        AttachmentApplication::cancelPending((int) $id, (int) $this->user['id'])
            ? flashSuccess('Request cancelled. You can send a new one for that category.')
            : flashError('Only a request that has not been accepted yet can be cancelled.');
        redirect('/account/attachment-providers');
    }

    /** The provider (for requests not sent to a branch) accepts every ticked student at once. */
    public function providerBulkAccept(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisations providing attachment can do this.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) Request::post('ids', [])))));
        $accepted = 0;
        foreach ($ids as $id) {
            $application = AttachmentApplication::findForProvider($id, (int) $this->user['id']);
            if ($application && empty($application['branch_id'])
                && in_array($application['status'], [AttachmentApplication::STATUS_PENDING, AttachmentApplication::STATUS_PAUSED], true)) {
                AttachmentApplication::setStatus($id, AttachmentApplication::STATUS_ACCEPTED);
                $accepted++;
            }
        }
        $accepted
            ? flashSuccess('Accepted ' . $accepted . ' student' . ($accepted === 1 ? '' : 's') . '.')
            : flashError($ids ? 'None of those students could be accepted.' : 'Tick at least one student to accept.');
        redirect('/account/people');
    }

    /** Excel download of every request to this provider (all its branches), optionally for a date range. */
    public function providerExport(): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisations providing attachment can do this.');
            redirect('/account/dashboard');
        }
        [$from, $to] = \App\Services\AttachmentReport::dateRange();
        \App\Services\AttachmentReport::download(
            AttachmentApplication::report(['provider_id' => (int) $this->user['id'], 'from' => $from, 'to' => $to]),
            'all-branches',
            $from,
            $to
        );
    }

    public function providerAccept(string $id): void
    {
        $this->providerDecide((int) $id, AttachmentApplication::STATUS_PENDING, AttachmentApplication::STATUS_ACCEPTED, 'Student accepted.');
    }

    public function providerComplete(string $id): void
    {
        $this->providerDecide((int) $id, AttachmentApplication::STATUS_ACCEPTED, AttachmentApplication::STATUS_RECOMMENDED, 'Attachment completed. Their recommendation letter is ready in their portal.');
    }

    /** An organisation with no branches reviews its own students; with branches, the branch admin does. */
    private function providerDecide(int $id, string $from, string $to, string $message): void
    {
        if (($this->user['role_slug'] ?? '') !== 'attachment_trainer') {
            flashError('Only organisations providing attachment can do this.');
            redirect('/account/dashboard');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/people');
        }
        $application = AttachmentApplication::findForProvider($id, (int) $this->user['id']);
        if (!$application || !empty($application['branch_id']) || $application['status'] !== $from) {
            flashError('That request could not be updated. Requests for a branch are handled by that branch admin.');
            redirect('/account/people');
        }
        if ($to === AttachmentApplication::STATUS_RECOMMENDED) {
            $owed = WalletService::studentBalanceOwed((int) $application['student_user_id']);
            if ($owed > 0) {
                flashError('This student still owes Ksh ' . number_format($owed, 2) . ' in course fees. They can be marked completed once the balance is Ksh 0.');
                redirect('/account/people');
            }
        }
        AttachmentApplication::setStatus($id, $to);
        flashSuccess($message);
        redirect('/account/people');
    }

    /** category id => the student's live request in it (anything but declined). */
    private function takenCategories(array $applications): array
    {
        $taken = [];
        foreach ($applications as $application) {
            if (!empty($application['category_id']) && $application['status'] !== AttachmentApplication::STATUS_REJECTED) {
                $taken[(int) $application['category_id']] = $application;
            }
        }
        return $taken;
    }

    /** The branch the student already chose at this provider (0 = the organisation itself), or null. */
    private function chosenBranchAt(int $providerId, array $applications): ?int
    {
        foreach ($applications as $application) {
            if ((int) $application['provider_user_id'] === $providerId && $application['status'] !== AttachmentApplication::STATUS_REJECTED) {
                return (int) ($application['branch_id'] ?? 0);
            }
        }
        return null;
    }

    private function requireStudent(): void
    {
        if (!Authz::isStudent($this->user)) {
            flashError('Only students choose an attachment provider.');
            redirect('/account/dashboard');
        }
    }

    /** Attachment is only offered to students who have enrolled for a course. */
    private function isEnrolledForAnyCourse(): bool
    {
        try {
            return CourseEnrollment::hasAny((int) $this->user['id']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The student's fees across all their courses (see WalletService::feeSummaries()). */
    private function feeStanding(): array
    {
        $id = (int) $this->user['id'];
        try {
            return WalletService::feeSummaries([$id])[$id];
        } catch (\Throwable $e) {
            return ['courses' => 0, 'fee' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'is_settled' => true,
                    'paid_ratio' => 1.0, 'below_minimum' => false, 'items' => []];
        }
    }

    /** Categories of the courses the student is enrolled in. */
    private function studentCategories(): array
    {
        try {
            return AttachmentAudience::studentCategories((int) $this->user['id']);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Providers that chose to appear under at least one of the student's
     * categories, each with 'matching_categories' (id => category row).
     */
    private function visibleProviders(array $studentCategories): array
    {
        if (!$studentCategories) {
            return [];
        }
        try {
            $targets = AttachmentAudience::providersForCategories(array_keys($studentCategories));
        } catch (\Throwable $e) {
            return [];
        }
        $providers = [];
        foreach (User::allAttachmentProviders() as $provider) {
            $categoryIds = $targets[(int) $provider['id']] ?? [];
            if (!$categoryIds) {
                continue;
            }
            $provider['matching_categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $provider['matching_categories'][$categoryId] = $studentCategories[$categoryId];
            }
            $providers[] = $provider;
        }
        return $providers;
    }

    private function visibleProvider(int $providerId): array
    {
        foreach ($this->visibleProviders($this->studentCategories()) as $provider) {
            if ((int) $provider['id'] === $providerId) {
                return $provider;
            }
        }
        flashError('Choose an attachment organisation from the list.');
        redirect('/account/attachment-providers');
    }
}
