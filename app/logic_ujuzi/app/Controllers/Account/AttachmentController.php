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
        $byCourse = $this->applicationsByCourse($applications);

        // Each enrolled course gets its own attachment: its own providers, and
        // one request. Once a course has a live request the other
        // organisations for it are greyed out.
        $courses = [];
        foreach ($isEnrolled ? $this->studentCourses() : [] as $course) {
            $application = $byCourse[$course['id']] ?? null;
            $course['application'] = $application;
            $course['locked'] = $application && $application['status'] !== AttachmentApplication::STATUS_REJECTED;
            $course['declined'] = $application ? AttachmentApplication::declinedProviderIds($application) : [];
            $course['providers'] = $this->providersForCourse($course);
            $course['fees'] = WalletService::courseStanding((int) $this->user['id'], (int) $course['id']);
            $courses[] = $course;
        }

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
            'applications' => $applications,
            'courses' => $courses,
        ]);
    }

    /** One provider's page for one of the student's courses: its branches as cards, each with its contacts. */
    public function show(string $id): void
    {
        $this->requireStudent();
        if (!$this->isEnrolledForAnyCourse()) {
            flashError('Enroll for a course first — attachment opens once you are enrolled.');
            redirect('/account/attachment-providers');
        }

        $course = $this->studentCourse((int) Request::query('course', 0));
        $provider = $this->providerForCourse($course, (int) $id);
        $application = $this->applicationsByCourse(AttachmentApplication::allForStudent((int) $this->user['id']))[$course['id']] ?? null;

        $this->render('account.attachment-providers.show', [
            'pageTitle' => $provider['organisation_name'] ?: userDisplayName($provider),
            'activeNav' => 'attachment_providers',
            'provider' => $provider,
            'course' => $course,
            'application' => $application,
            'declined' => $application ? AttachmentApplication::declinedProviderIds($application) : [],
            'feeStanding' => WalletService::courseStanding((int) $this->user['id'], (int) $course['id']),
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

        $course = $this->studentCourse((int) Request::post('course_id', 0));
        // Only this course's own fee counts — never a total across courses.
        $standing = WalletService::courseStanding((int) $this->user['id'], (int) $course['id']);
        if ($standing['below_minimum']) {
            flashError('You have paid ' . (int) floor($standing['paid_ratio'] * 100) . '% of the ' . $course['title'] . ' fee. Pay at least ' . WalletService::minPaymentPercent() . '% of it before sending its attachment request.');
            redirect('/account/attachment-providers#course-' . $course['id']);
        }

        $providerId = (int) Request::post('provider_id', 0);
        $branchId = (int) Request::post('branch_id', 0);
        $categoryId = (int) Request::post('category_id', 0);
        $provider = $this->providerForCourse($course, $providerId);
        $back = '/account/attachment-providers/' . $providerId . '?course=' . $course['id'];

        // The category is mandatory: one of this course's categories the provider appears under.
        if (!isset($provider['matching_categories'][$categoryId])) {
            flashError('Choose the category this attachment is for.');
            redirect($back);
        }

        // The branch is mandatory when the organisation has branches; its admin reviews the request.
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

        $refused = AttachmentApplication::saveCourseSelection((int) $this->user['id'], (int) $course['id'], $categoryId, $providerId, $branchId > 0 ? $branchId : null);
        if ($refused !== null) {
            flashError($refused);
            redirect($back);
        }

        \App\Services\Notifier::newAttachmentRequest((int) $this->user['id'], $providerId, $branchId > 0 ? $branchId : null, (string) $course['title']);
        flashSuccess(($branchId > 0 ? 'Request sent. The branch admin will review and accept you' : 'Request sent. The organisation will review and accept you')
            . ' for ' . $course['title'] . '.');
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
        if ($to === AttachmentApplication::STATUS_RECOMMENDED && !\App\Models\AttachmentAssessment::isMarked($id)) {
            flashError(\App\Models\AttachmentAssessment::NOT_MARKED);
            redirect('/account/attachment-requests/' . $id . '#assessment');
        }
        if ($to === AttachmentApplication::STATUS_RECOMMENDED) {
            $owed = WalletService::requestFees((int) $application['student_user_id'], !empty($application['category_id']) ? (int) $application['category_id'] : null, !empty($application['course_id']) ? (int) $application['course_id'] : null)['balance'];
            if ($owed > 0) {
                flashError(WalletService::outstandingMessage($owed));
                redirect('/account/people');
            }
        }
        AttachmentApplication::setStatus($id, $to);
        flashSuccess($message);
        redirect('/account/people');
    }

    /** course id => the student's request for that course. */
    private function applicationsByCourse(array $applications): array
    {
        $out = [];
        foreach ($applications as $application) {
            if (!empty($application['course_id'])) {
                $out[(int) $application['course_id']] = $application;
            }
        }
        return $out;
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

    /** The student's enrolled courses (see AttachmentAudience::studentCourses()). */
    private function studentCourses(): array
    {
        try {
            return AttachmentAudience::studentCourses((int) $this->user['id']);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function studentCourse(int $courseId): array
    {
        $course = $this->studentCourses()[$courseId] ?? null;
        if (!$course) {
            flashError('Choose one of your courses first.');
            redirect('/account/attachment-providers');
        }
        return $course;
    }

    /**
     * Providers approved to appear under at least one of the course's
     * categories, each with 'matching_categories' (id => name) and
     * 'is_internal' (it belongs to the course's own organisation). Internal
     * providers come first.
     */
    private function providersForCourse(array $course): array
    {
        try {
            $targets = AttachmentAudience::providersForCategories(array_keys($course['categories']));
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
                $provider['matching_categories'][$categoryId] = $course['categories'][$categoryId];
            }
            $provider['is_internal'] = (int) ($provider['organisation_id'] ?? 0) === $course['organisation_id'];
            $providers[] = $provider;
        }
        usort($providers, static fn(array $a, array $b): int => (int) $b['is_internal'] <=> (int) $a['is_internal']);
        return $providers;
    }

    private function providerForCourse(array $course, int $providerId): array
    {
        foreach ($this->providersForCourse($course) as $provider) {
            if ((int) $provider['id'] === $providerId) {
                return $provider;
            }
        }
        flashError('Choose an attachment organisation from the list for this course.');
        redirect('/account/attachment-providers');
    }
}
