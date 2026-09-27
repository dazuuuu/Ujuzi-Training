<?php

namespace App\Models;

use App\Core\Database;

/**
 * Notes on one attachment request, between its reviewer (the branch admin,
 * or the provider itself when it has no branches) and the student.
 */
class AttachmentMessage
{
    public const SIDE_STUDENT = 'student';
    public const SIDE_REVIEWER = 'reviewer';
    public const MAX_LENGTH = 4000;

    public static function add(int $applicationId, int $senderUserId, string $side, string $body, ?string $statusChange = null): void
    {
        Database::connection()->prepare(
            'INSERT INTO attachment_messages (application_id, sender_user_id, sender_side, body, status_change) VALUES (?, ?, ?, ?, ?)'
        )->execute([$applicationId, $senderUserId, $side, mb_substr($body, 0, self::MAX_LENGTH), $statusChange]);
    }

    /** The whole thread, oldest first, with each sender's name. */
    public static function forApplication(int $applicationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.first_name, u.last_name, u.email
             FROM attachment_messages m
             INNER JOIN users u ON u.id = m.sender_user_id
             WHERE m.application_id = ?
             ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute([$applicationId]);
        return $stmt->fetchAll();
    }

    /** Marks everything the other side sent as read by $readerSide. */
    public static function markRead(int $applicationId, string $readerSide): void
    {
        Database::connection()->prepare(
            'UPDATE attachment_messages SET read_at = NOW() WHERE application_id = ? AND sender_side <> ? AND read_at IS NULL'
        )->execute([$applicationId, $readerSide]);
    }

    /** @return array<int, int> application id => messages from the other side that $readerSide hasn't read */
    public static function unreadCounts(array $applicationIds, string $readerSide): array
    {
        $applicationIds = array_values(array_unique(array_filter(array_map('intval', $applicationIds))));
        if (!$applicationIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT application_id, COUNT(*) AS unread FROM attachment_messages
             WHERE application_id IN ($placeholders) AND sender_side <> ? AND read_at IS NULL
             GROUP BY application_id"
        );
        $stmt->execute(array_merge($applicationIds, [$readerSide]));
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['application_id']] = (int) $row['unread'];
        }
        return $out;
    }

    /** The newest message on each request, for list views. */
    public static function latestFor(array $applicationIds): array
    {
        $applicationIds = array_values(array_unique(array_filter(array_map('intval', $applicationIds))));
        if (!$applicationIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT m.* FROM attachment_messages m
             INNER JOIN (SELECT application_id, MAX(id) AS id FROM attachment_messages
                         WHERE application_id IN ($placeholders) GROUP BY application_id) latest ON latest.id = m.id"
        );
        $stmt->execute($applicationIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['application_id']] = $row;
        }
        return $out;
    }
}
