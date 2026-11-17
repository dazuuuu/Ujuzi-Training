<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Url;
use App\Services\MailerService;
use App\Services\SmsService;
use App\Models\StoreSetting;

class Announcement
{
    /** Who may send: tutors, organisation admins and course branch admins. */
    public static function canSend(array $user): bool
    {
        return in_array($user['role_slug'] ?? '', ['trainer', 'organisation_admin', 'course_branch_admin'], true) && self::organisationOf($user) > 0;
    }

    public static function organisationOf(array $user): int
    {
        if (!empty($user['organisation_id'])) {
            return (int) $user['organisation_id'];
        }
        if (($user['role_slug'] ?? '') === 'course_branch_admin') {
            return (int) (OrganisationBranch::forBranchAdmin((int) $user['id'])[0]['organisation_id'] ?? 0);
        }
        return 0;
    }

    /** Branch ids the sender may target (a branch admin only their own). */
    public static function branchesFor(array $user): array
    {
        $org = self::organisationOf($user);
        if (($user['role_slug'] ?? '') === 'course_branch_admin') {
            return array_values(array_filter(OrganisationBranch::forBranchAdmin((int) $user['id']), static fn(array $b): bool => (int) $b['organisation_id'] === $org));
        }
        return OrganisationBranch::forOrganisation($org);
    }

    public static function categoriesFor(array $user): array
    {
        $org = self::organisationOf($user);
        if (($user['role_slug'] ?? '') === 'course_branch_admin') {
            return OrganisationCategory::forBranches($org, array_map(static fn(array $b): int => (int) $b['id'], self::branchesFor($user)), true);
        }
        return OrganisationCategory::forOrganisation($org, true);
    }

    /** Approved students of the organisation, narrowed by branch and category. @return array<int, array> user rows */
    public static function audience(array $sender, array $branchIds, array $categoryIds): array
    {
        $org = self::organisationOf($sender);
        $allowedBranches = array_map(static fn(array $b): int => (int) $b['id'], self::branchesFor($sender));
        $branchIds = array_values(array_intersect(array_map('intval', $branchIds), $allowedBranches));
        if (($sender['role_slug'] ?? '') === 'course_branch_admin' && !$branchIds) {
            $branchIds = $allowedBranches; // a branch admin never reaches beyond their own branches
        }
        $sql = "SELECT m.user_id, m.category_ids, u.first_name, u.last_name, u.email, u.phone
                FROM organisation_memberships m
                INNER JOIN users u ON u.id = m.user_id AND u.is_active = 1
                INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
                WHERE m.organisation_id = ? AND m.status = 'approved'";
        if ($branchIds) {
            $sql .= ' AND m.branch_id IN (' . implode(',', $branchIds) . ')';
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$org]);
        $out = [];
        $categoryIds = array_map('intval', $categoryIds);
        foreach ($stmt->fetchAll() as $row) {
            if ($categoryIds) {
                $has = array_map('intval', json_decode((string) ($row['category_ids'] ?? ''), true) ?: []);
                if (!array_intersect($categoryIds, $has)) {
                    continue;
                }
            }
            $out[(int) $row['user_id']] = $row;
        }
        return $out;
    }

    public static function create(array $sender, array $data, array $recipients): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $pdo->prepare(
            'INSERT INTO announcements (author_user_id, organisation_id, kind, title, body, attachment_path, attachment_name, audience_branch_ids, audience_category_ids, notify_email, notify_sms, recipient_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $sender['id'], self::organisationOf($sender), 'announcement', $data['title'], $data['body'] ?: null,
            $data['attachment_path'] ?: null, $data['attachment_name'] ?: null,
            json_encode($data['branch_ids']), json_encode($data['category_ids']),
            $data['notify_email'] ? 1 : 0, $data['notify_sms'] ? 1 : 0, count($recipients),
        ]);
        $id = (int) $pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT IGNORE INTO announcement_recipients (announcement_id, user_id) VALUES (?, ?)');
        foreach (array_keys($recipients) as $uid) {
            $ins->execute([$id, $uid]);
        }
        $pdo->commit();
        return $id;
    }

    /** Email and/or SMS: "Kindly check your inbox, you have a new confidential message". Never throws. */
    public static function notify(array $recipients, bool $email, bool $sms, string $text, string $path = '/account/announcements'): void
    {
        try {
            if ($email && MailerService::isConfigured()) {
                foreach ($recipients as $r) {
                    if (trim((string) ($r['email'] ?? '')) === '') {
                        continue;
                    }
                    try {
                        MailerService::sendNotice((string) $r['email'], 'New message', 'You have a new notification',
                            'Hello ' . trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) . ",\n\nKindly check your inbox. " . $text, 'Open my inbox', Url::absolute($path));
                    } catch (\Throwable $e) {
                    }
                }
            }
            if ($sms) {
                $phones = [];
                foreach ($recipients as $r) {
                    if (($p = SmsService::msisdn((string) ($r['phone'] ?? ''))) !== '') {
                        $phones[] = $p;
                    }
                }
                SmsService::send($phones, 'Hello, kindly check your inbox. ' . $text);
            }
        } catch (\Throwable $e) {
        }
    }

    public static function forUser(int $userId, int $limit = 100): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT a.*, r.read_at, u.first_name AS author_first, u.last_name AS author_last, o.name AS organisation_name
                 FROM announcement_recipients r
                 INNER JOIN announcements a ON a.id = r.announcement_id
                 LEFT JOIN users u ON u.id = a.author_user_id
                 LEFT JOIN organisations o ON o.id = a.organisation_id
                 WHERE r.user_id = ? ORDER BY a.id DESC LIMIT ' . (int) $limit
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function sentBy(int $userId): array
    {
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM announcements WHERE author_user_id = ? ORDER BY id DESC LIMIT 100');
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM announcement_recipients WHERE user_id = ? AND read_at IS NULL');
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** The announcement if this person received or sent it, marking it read for a recipient. */
    public static function openFor(int $id, int $userId): ?array
    {
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare(
                'SELECT a.*, u.first_name AS author_first, u.last_name AS author_last, o.name AS organisation_name,
                        (SELECT COUNT(*) FROM announcement_recipients x WHERE x.announcement_id = a.id AND x.user_id = ?) AS is_recipient
                 FROM announcements a LEFT JOIN users u ON u.id = a.author_user_id LEFT JOIN organisations o ON o.id = a.organisation_id WHERE a.id = ?'
            );
            $stmt->execute([$userId, $id]);
            $row = $stmt->fetch();
            if (!$row || ((int) $row['author_user_id'] !== $userId && !(int) $row['is_recipient'])) {
                return null;
            }
            if ((int) $row['is_recipient']) {
                $pdo->prepare('UPDATE announcement_recipients SET read_at = COALESCE(read_at, NOW()) WHERE announcement_id = ? AND user_id = ?')->execute([$id, $userId]);
            }
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** A one-off system notice to a single user (e.g. "your certificate is ready") — bell + inbox, no sender. */
    public static function systemNotify(int $userId, string $title, string $body, string $path = '/account/announcements'): void
    {
        if ($userId < 1) {
            return;
        }
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO announcements (author_user_id, organisation_id, kind, title, body, notify_email, notify_sms, recipient_count)
                 VALUES (0, NULL, 'system', ?, ?, 0, 0, 1)"
            )->execute([$title, $body]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT IGNORE INTO announcement_recipients (announcement_id, user_id) VALUES (?, ?)')->execute([$id, $userId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    /** "You have a new course published" to approved learners of the course's categories (ticks in Settings). */
    public static function courseApproved(array $course): void
    {
        try {
            $email = StoreSetting::get('notify_course_email', '1') !== '0';
            $sms = StoreSetting::get('notify_course_sms', '0') === '1';
            if (!$email && !$sms) {
                return;
            }
            $categoryIds = array_map('intval', Course::categoryIdsFor((int) $course['id']));
            $stmt = Database::connection()->prepare(
                "SELECT m.user_id, m.category_ids, u.first_name, u.last_name, u.email, u.phone
                 FROM organisation_memberships m INNER JOIN users u ON u.id = m.user_id AND u.is_active = 1
                 INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
                 WHERE m.organisation_id = ? AND m.status = 'approved'"
            );
            $stmt->execute([(int) $course['organisation_id']]);
            $recipients = [];
            foreach ($stmt->fetchAll() as $row) {
                $has = array_map('intval', json_decode((string) ($row['category_ids'] ?? ''), true) ?: []);
                if (!$categoryIds || array_intersect($categoryIds, $has)) {
                    $recipients[(int) $row['user_id']] = $row;
                }
            }
            self::notify($recipients, $email, $sms, 'You have a new course published: ' . $course['title'] . '.', '/account/courses/' . (int) $course['id']);
        } catch (\Throwable $e) {
        }
    }
}
