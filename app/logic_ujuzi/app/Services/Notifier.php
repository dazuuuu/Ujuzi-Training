<?php

namespace App\Services;

use App\Core\Url;
use App\Models\AttachmentApplication;
use App\Models\User;

/**
 * Email notices for what happens on the platform: attachment requests and
 * decisions, organisation approvals, enrolments, completed courses and
 * course approvals. A notice that can't be sent (email not set up, no email
 * address) is skipped quietly — the action itself always goes ahead.
 */
class Notifier
{
    public static function toUser(int $userId, string $subject, string $heading, string $message, string $buttonLabel, string $path): void
    {
        try {
            $user = User::find($userId);
            if (!$user || trim((string) ($user['email'] ?? '')) === '' || !MailerService::isConfigured()) {
                return;
            }
            MailerService::sendNotice((string) $user['email'], $subject, $heading, 'Hello ' . userDisplayName($user) . ",\n\n" . $message, $buttonLabel, Url::absolute($path));
        } catch (\Throwable $e) {
            // Never let an email problem stop the action.
        }
    }

    /** The student hears about every decision on their attachment request. */
    public static function attachmentStatus(int $applicationId, string $status): void
    {
        $a = AttachmentApplication::findDetailed($applicationId);
        if (!$a) {
            return;
        }
        $where = trim(($a['provider_organisation_name'] ?: trim(($a['provider_first_name'] ?? '') . ' ' . ($a['provider_last_name'] ?? ''))) . (!empty($a['branch_title']) ? ' ' . $a['branch_title'] : ''));
        $course = !empty($a['course_title']) ? ' for ' . $a['course_title'] : '';
        [$subject, $message] = match ($status) {
            AttachmentApplication::STATUS_ACCEPTED => ['You have been accepted for attachment at ' . $where, 'Good news — you have been accepted for attachment at ' . $where . $course . '. Open your attachment page for the details and any notes.'],
            AttachmentApplication::STATUS_PAUSED => ['Your attachment request is on hold', 'Your attachment request at ' . $where . $course . ' has been put on hold. Check the notes on your request for what to do next.'],
            AttachmentApplication::STATUS_REJECTED => ['Your attachment request was declined', $where . ' declined your attachment request' . $course . ($a['provider_note'] ? ': ' . $a['provider_note'] : '.') . ' You can choose another organisation.'],
            AttachmentApplication::STATUS_RECOMMENDED, AttachmentApplication::STATUS_COMPLETED => ['Your attachment is complete', 'Your attachment at ' . $where . $course . ' is complete. Your recommendation letter is ready in your portal.'],
            default => [null, null],
        };
        if ($subject) {
            self::toUser((int) $a['student_user_id'], $subject, $subject, $message, 'Open my attachment', '/account/attachments/' . $applicationId);
        }
    }

    /** The branch admin (or the provider, without branches) hears about a new request. */
    public static function newAttachmentRequest(int $studentId, int $providerId, ?int $branchId, string $courseTitle): void
    {
        $student = User::find($studentId);
        $name = $student ? userFullName($student) : 'A student';
        $reviewer = $providerId;
        if ($branchId) {
            $branch = \App\Models\OrganisationBranch::findActive($branchId);
            if (!empty($branch['branch_admin_user_id'])) {
                $reviewer = (int) $branch['branch_admin_user_id'];
            }
        }
        $path = $reviewer === $providerId ? '/account/people' : '/account/branch-admin';
        self::toUser($reviewer, 'New attachment request from ' . $name, 'New attachment request', $name . ' asked to do their attachment with you' . ($courseTitle !== '' ? ' for ' . $courseTitle : '') . '. Open your requests to accept or decline.', 'Review requests', $path);
    }
}
