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
        App::STATUS_PENDING => ['accept' => 'Accept', 'pause' => 'Put on hold', 'decline' => 'Decline'],
        App::STATUS_PAUSED => ['accept' => 'Accept', 'decline' => 'Decline'],
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

        $this->render('account.attachment-review.show', [
            'pageTitle' => 'Attachment request',
            'activeNav' => $this->backNav(),
            'application' => $application,
            'fees' => $fees,
            'messages' => AttachmentMessage::forApplication($application['id']),
            'actions' => self::ACTIONS[$application['status']] ?? [],
            'backUrl' => $this->backUrl(),
        ]);
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

        if ($newStatus === App::STATUS_RECOMMENDED) {
            $owed = WalletService::studentBalanceOwed($application['student_user_id']);
            if ($owed > 0) {
                flashError('This student still owes Ksh ' . number_format($owed, 2) . ' in course fees. They can be marked completed once the balance is Ksh 0.');
                redirect($here);
            }
        }

        if ($newStatus !== null) {
            // A decline's note is also what the student sees as the reason.
            App::setStatus($application['id'], $newStatus, $newStatus === App::STATUS_REJECTED && $body !== '' ? $body : null);
        }
        AttachmentMessage::add($application['id'], (int) $this->user['id'], AttachmentMessage::SIDE_REVIEWER, $body, $newStatus);

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

    /** Sends the student their recommendation letter again: in their portal, and by email when email is set up. */
    public function resendLetter(string $id): void
    {
        $application = $this->reviewable((int) $id);
        $here = '/account/attachment-requests/' . $application['id'];
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
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
