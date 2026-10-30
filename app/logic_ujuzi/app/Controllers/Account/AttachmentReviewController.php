<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\AttachmentApplication as App;
use App\Models\AttachmentMessage;
use App\Services\MailerException;
use App\Services\MailerService;
use App\Services\WalletService;

/**
 * One attachment request as its reviewer sees it — the branch admin, or the
 * provider itself when the request isn't for a branch. The reviewer can send
 * the student a note, with or without changing the status, and resend the
 * recommendation letter once the attachment is completed.
 */
class AttachmentReviewController extends BaseAccountController
{
    /** What each status can move to, and the button label for it. */
    public const ACTIONS = [
        // The provider (or its branch) can mark the attachment completed at any stage.
        App::STATUS_PENDING => ['accept' => 'Accept', 'pause' => 'Put on hold', 'complete' => 'Mark completed', 'decline' => 'Decline'],
        App::STATUS_PAUSED => ['accept' => 'Accept', 'complete' => 'Mark completed', 'decline' => 'Decline'],
        App::STATUS_ACCEPTED => ['pause' => 'Put on hold', 'complete' => 'Mark completed', 'decline' => 'Decline'],
        App::STATUS_RECOMMENDED => [],
        App::STATUS_COMPLETED => [],
        App::STATUS_REJECTED => [],
    ];

    private const ACTION_STATUS = [
        'accept' => App::STATUS_ACCEPTED,
        'pause' => App::STATUS_PAUSED,
        'decline' => App::STATUS_REJECTED,
        'complete' => App::STATUS_RECOMMENDED,
    ];

    public function show(string $id): void
    {
        $application = $this->reviewable((int) $id);
        AttachmentMessage::markRead($application['id'], AttachmentMessage::SIDE_REVIEWER);
        $fees = WalletService::feeSummaries([$application['student_user_id']])[$application['student_user_id']] ?? null;

        $this->render('account.attachment-review.show', $this->sheetData($application) + [
            'pageTitle' => 'Attachment request',
            'activeNav' => $this->backNav(),
            'application' => $application,
            'fees' => $fees,
            'requestFees' => WalletService::requestFees($application['student_user_id'], $application['category_id'] ?? null, !empty($application['course_id']) ? (int) $application['course_id'] : null),
            'messages' => AttachmentMessage::forApplication($application['id']),
            // A branch's requests are decided by its branch admin; the organisation only views them.
            'actions' => $this->canFill($application) ? (self::ACTIONS[$application['status']] ?? []) : [],
            'backUrl' => $this->backUrl(),
        ]);
    }

    /** Only the assessment, on its own page. */
    public function assessmentPage(string $id): void
    {
        $application = $this->reviewable((int) $id);
        $this->render('account.attachment-review.assessment', $this->sheetData($application) + [
            'pageTitle' => 'Assessment',
            'activeNav' => $this->backNav(),
            'application' => $application,
            'backUrl' => $this->backUrl(),
        ]);
    }

    private function sheetData(array $application): array
    {
        $saved = \App\Models\AttachmentAssessment::forApplication((int) $application['id']);
        return [
            'assessment' => $saved,
            'form' => \App\Models\AttachmentAssessment::formFor((int) $application['provider_user_id'], $saved),
            'canFill' => $this->canFill($application),
        ];
    }

    /** The branch admin fills a branch's attachees; the organisation fills those not sent to a branch. */
    private function canFill(array $application): bool
    {
        return ($this->user['role_slug'] ?? '') === 'branch_admin' || empty($application['branch_id']);
    }

    /** A note to the student, optionally with a status change. Either one on its own is fine. */
    public function respond(string $id): void
    {
        $application = $this->reviewable((int) $id);
        $here = '/account/attachment-requests/' . $application['id'];
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($here);
        }
        if (!$this->canFill($application)) {
            flashError('This attachee is handled by the branch admin of ' . ($application['branch_title'] ?? 'the branch') . '.');
            redirect($here);
        }

        $body = trim((string) Request::post('body', ''));
        $action = (string) Request::post('action', '');
        $newStatus = null;
        if ($action !== '') {
            if (!isset((self::ACTIONS[$application['status']] ?? [])[$action])) {
                flashError('That change is not possible from its current status.');
                redirect($here);
            }
            $newStatus = self::ACTION_STATUS[$action];
        }
        if ($body === '' && $newStatus === null) {
            flashError('Write a note, choose a new status, or both.');
            redirect($here);
        }
        if (mb_strlen($body) > AttachmentMessage::MAX_LENGTH) {
            flashError('Keep the note under ' . AttachmentMessage::MAX_LENGTH . ' characters.');
            redirect($here);
        }

        // Marks are only needed when the attachment counts towards a course that requires it.
        if ($newStatus === App::STATUS_RECOMMENDED && App::needsAssessment($application) && !\App\Models\AttachmentAssessment::isMarked((int) $application['id'])) {
            flashError(\App\Models\AttachmentAssessment::NOT_MARKED);
            redirect($here . '#assessment');
        }
        if ($newStatus === App::STATUS_RECOMMENDED) {
            $owed = WalletService::requestFees((int) $application['student_user_id'], !empty($application['category_id']) ? (int) $application['category_id'] : null, !empty($application['course_id']) ? (int) $application['course_id'] : null)['balance'];
            if ($owed > 0) {
                flashError(WalletService::outstandingMessage($owed));
                redirect($here);
            }
        }

        if ($newStatus !== null) {
            // A decline's note is also what the student sees as the reason.
            App::setStatus($application['id'], $newStatus, $newStatus === App::STATUS_REJECTED && $body !== '' ? $body : null);
        }
        AttachmentMessage::add($application['id'], (int) $this->user['id'], AttachmentMessage::SIDE_REVIEWER, $body, $newStatus);

        if (Request::post('return') === 'list') {
            $here = $this->backUrl();
        }
        flashSuccess(match ($newStatus) {
            null => 'Note sent to the student.',
            App::STATUS_ACCEPTED => 'Student accepted' . ($body !== '' ? ' and your note sent.' : '.'),
            App::STATUS_PAUSED => 'Request put on hold' . ($body !== '' ? ' and your note sent.' : '.'),
            App::STATUS_REJECTED => 'Request declined' . ($body !== '' ? ' and your note sent.' : '.'),
            App::STATUS_RECOMMENDED => 'Attachment completed. Their recommendation letter is ready in their portal.',
            default => 'Saved.',
        });
        redirect($here);
    }

    /** Saves the daily log (date, what they did, score, remarks per day) and the closing remark. */
    public function assessment(string $id): void
    {
        $application = App::findForReviewer((int) $id, $this->user);
        $here = '/account/attachment-requests/' . (int) $id . '#assessment';
        if (!$application || !csrfVerify(Request::post('csrf_token'))) {
            flashError('That request could not be found, or your session expired.');
            redirect('/account/branch-admin');
        }
        if (!$this->canFill($application)) {
            flashError('The branch admin fills this attachee\'s assessment.');
            redirect($here);
        }
        $saved = \App\Models\AttachmentAssessment::forApplication((int) $application['id']);
        \App\Models\AttachmentAssessment::save(
            (int) $application['id'],
            (array) Request::post('items', []),
            (string) Request::post('remarks', ''),
            (int) $this->user['id'],
            \App\Models\AttachmentAssessment::formFor((int) $application['provider_user_id'], $saved),
            (array) Request::post('answers', [])
        );
        if (Request::post('page') === 'assessment') {
            $here = '/account/attachment-requests/' . (int) $id . '/assessment';
        }
        flashSuccess(trim((string) Request::post('remarks', '')) === '' ? 'Assessment saved. Add the closing remark when the attachment ends.' : 'Assessment saved and closed. Complete the attachment to send it to the course organisation.');
        redirect($here);
    }

    /** Sends the student their recommendation letter again: in their portal, and by email when email is set up. */
    public function resendLetter(string $id): void
    {
        $application = $this->reviewable((int) $id);
        $here = '/account/attachment-requests/' . $application['id'];
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($here);
        }
        if (!$this->canFill($application)) {
            flashError('The branch admin resends this attachee\'s letter.');
            redirect($here);
        }
        if ($application['status'] !== App::STATUS_RECOMMENDED) {
            flashError('The recommendation letter is only available once the attachment is completed.');
            redirect($here);
        }

        $letterUrl = absoluteUrl('/account/recommendation-letter/' . $application['id']);
        $note = trim((string) Request::post('body', ''));
        AttachmentMessage::add(
            $application['id'],
            (int) $this->user['id'],
            AttachmentMessage::SIDE_REVIEWER,
            'Your recommendation letter has been sent to you again. Open it here: ' . $letterUrl . ($note !== '' ? "\n\n" . $note : ''),
            'letter_resent'
        );
        App::markLetterSent($application['id']);

        $emailed = false;
        $emailError = '';
        if (!empty($application['email'])) {
            try {
                MailerService::sendRecommendationLetter(
                    (string) $application['email'],
                    trim($application['first_name'] . ' ' . $application['last_name']),
                    (string) ($application['provider_organisation_name'] ?: 'your attachment provider'),
                    $letterUrl,
                    $note
                );
                $emailed = true;
            } catch (MailerException $e) {
                $emailError = $e->getMessage();
            }
        }

        $emailed
            ? flashSuccess('Recommendation letter resent — it is in the student\'s portal and was emailed to ' . $application['email'] . '.')
            : flashSuccess('Recommendation letter resent to the student\'s portal.' . ($emailError !== '' ? ' It was not emailed: ' . $emailError : ''));
        redirect($here);
    }

    private function reviewable(int $id): array
    {
        $application = App::findForReviewer($id, $this->user);
        if (!$application) {
            flashError('That attachment request could not be found for you.');
            redirect($this->backUrl());
        }
        return $application;
    }

    private function backUrl(): string
    {
        return ($this->user['role_slug'] ?? '') === 'attachment_trainer' ? '/account/people' : '/account/branch-admin';
    }

    private function backNav(): string
    {
        return ($this->user['role_slug'] ?? '') === 'attachment_trainer' ? 'people' : 'branch_admin';
    }
}
