<?php

namespace App\Models;

use App\Core\Database;

/**
 * Where an attachment provider appears. A provider asks organisations
 * providing courses to take their students, choosing categories within each.
 * The organisation approves or declines; a student sees the provider once
 * approved and enrolled in a course under one of those categories.
 */
class AttachmentAudience
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /** @return int[] every category the provider asked for, whatever its status */
    public static function categoryIdsFor(int $providerUserId): array
    {
        $stmt = Database::connection()->prepare('SELECT category_id FROM attachment_provider_audiences WHERE provider_user_id = ?');
        $stmt->execute([$providerUserId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return array<int, string> category id => pending | approved | rejected */
    public static function statusesFor(int $providerUserId): array
    {
        $stmt = Database::connection()->prepare('SELECT category_id, status FROM attachment_provider_audiences WHERE provider_user_id = ?');
        $stmt->execute([$providerUserId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['category_id']] = (string) $row['status'];
        }
        return $out;
    }

    /**
     * Providers asking to take an organisation's students, one entry per
     * provider with its requested categories and their statuses.
     */
    public static function requestsForOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT a.provider_user_id, a.category_id, a.status, a.created_at, a.reviewed_at,
                    c.name AS category_name,
                    u.email, u.phone, u.first_name, u.last_name, u.organisation_id AS provider_organisation_id,
                    o.name AS provider_organisation_name, o.location AS provider_location
             FROM attachment_provider_audiences a
             INNER JOIN organisation_categories c ON c.id = a.category_id
             INNER JOIN users u ON u.id = a.provider_user_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE a.organisation_id = ? AND u.is_active = 1
             ORDER BY a.created_at DESC, c.sort_order ASC, c.name ASC"
        );
        $stmt->execute([$organisationId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int) $row['provider_user_id'];
            if (!isset($out[$pid])) {
                $out[$pid] = [
                    'provider_user_id' => $pid,
                    'name' => $row['provider_organisation_name'] ?: trim($row['first_name'] . ' ' . $row['last_name']) ?: $row['email'],
                    'organisation_id' => (int) ($row['provider_organisation_id'] ?? 0),
                    'location' => $row['provider_location'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'requested_at' => $row['created_at'],
                    'categories' => [],
                    'counts' => [self::STATUS_PENDING => 0, self::STATUS_APPROVED => 0, self::STATUS_REJECTED => 0],
                ];
            }
            $out[$pid]['categories'][] = ['id' => (int) $row['category_id'], 'name' => $row['category_name'], 'status' => $row['status']];
            $out[$pid]['counts'][$row['status']]++;
        }
        return array_values($out);
    }

    public static function pendingCountForOrganisation(int $organisationId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(DISTINCT provider_user_id) FROM attachment_provider_audiences WHERE organisation_id = ? AND status = 'pending'"
        );
        $stmt->execute([$organisationId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * The organisation's answer to one provider: the categories in
     * $approvedCategoryIds are approved, every other one it asked for is
     * declined. Returns false when the provider asked nothing of it.
     */
    public static function decide(int $organisationId, int $providerUserId, array $approvedCategoryIds, int $reviewerUserId): bool
    {
        $approved = array_map('intval', $approvedCategoryIds);
        $stmt = Database::connection()->prepare(
            'SELECT category_id FROM attachment_provider_audiences WHERE organisation_id = ? AND provider_user_id = ?'
        );
        $stmt->execute([$organisationId, $providerUserId]);
        $asked = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if (!$asked) {
            return false;
        }
        $update = Database::connection()->prepare(
            'UPDATE attachment_provider_audiences SET status = ?, reviewed_by_user_id = ?, reviewed_at = NOW()
             WHERE organisation_id = ? AND provider_user_id = ? AND category_id = ?'
        );
        foreach ($asked as $categoryId) {
            $status = in_array($categoryId, $approved, true) ? self::STATUS_APPROVED : self::STATUS_REJECTED;
            $update->execute([$status, $reviewerUserId, $organisationId, $providerUserId, $categoryId]);
        }
        return true;
    }

    /**
     * Replaces the provider's audience. Only active categories of active,
     * student-visible organisations are kept; anything else is dropped.
     * Returns how many categories were saved.
     */
    public static function sync(int $providerUserId, array $categoryIds): int
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds), static fn(int $id): bool => $id > 0)));
        $valid = [];
        if ($categoryIds) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $stmt = Database::connection()->prepare(
                "SELECT c.id, c.organisation_id
                 FROM organisation_categories c
                 INNER JOIN organisations o ON o.id = c.organisation_id
                 WHERE c.id IN ($placeholders) AND c.is_active = 1 AND o.is_active = 1 AND o.visible_to_students = 1"
            );
            $stmt->execute($categoryIds);
            $valid = $stmt->fetchAll();
        }

        // Categories kept keep their status (approved stays approved);
        // newly asked-for ones wait for the organisation.
        $previous = self::statusesFor($providerUserId);
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM attachment_provider_audiences WHERE provider_user_id = ?')->execute([$providerUserId]);
            $insert = $pdo->prepare(
                'INSERT INTO attachment_provider_audiences (provider_user_id, organisation_id, category_id, status, reviewed_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($valid as $row) {
                $status = $previous[(int) $row['id']] ?? self::STATUS_PENDING;
                $insert->execute([$providerUserId, (int) $row['organisation_id'], (int) $row['id'], $status, $status === self::STATUS_PENDING ? null : date('Y-m-d H:i:s')]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return count($valid);
    }

    /**
     * The course categories a student can request attachment under: the
     * categories of the courses they are enrolled in, keyed by id, each with
     * its organisation and the titles of those enrolled courses.
     */
    public static function studentCategories(int $studentUserId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT cat.id, cat.name, cat.organisation_id, o.name AS organisation_name, c.title AS course_title
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN course_categories cc ON cc.course_id = c.id
             INNER JOIN organisation_categories cat ON cat.id = cc.category_id
             INNER JOIN organisations o ON o.id = cat.organisation_id
             WHERE e.user_id = ? AND cat.is_active = 1 AND o.is_active = 1
             ORDER BY o.name ASC, cat.sort_order ASC, cat.name ASC, c.title ASC'
        );
        $stmt->execute([$studentUserId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (!isset($out[$id])) {
                $out[$id] = [
                    'id' => $id,
                    'name' => $row['name'],
                    'organisation_id' => (int) $row['organisation_id'],
                    'organisation_name' => $row['organisation_name'],
                    'courses' => [],
                ];
            }
            if (!in_array($row['course_title'], $out[$id]['courses'], true)) {
                $out[$id]['courses'][] = $row['course_title'];
            }
        }
        return $out;
    }

    /**
     * The student's enrolled courses that belong to at least one active
     * category, keyed by course id: id, title, organisation_id,
     * organisation_name, requires_attachment and categories (id => name).
     * Courses that need attachment come first.
     */
    public static function studentCourses(int $studentUserId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.title, c.organisation_id, c.requires_attachment, o.name AS organisation_name,
                    cat.id AS category_id, cat.name AS category_name
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN course_categories cc ON cc.course_id = c.id
             INNER JOIN organisation_categories cat ON cat.id = cc.category_id AND cat.is_active = 1
             INNER JOIN organisations o ON o.id = c.organisation_id
             WHERE e.user_id = ?
             ORDER BY c.requires_attachment DESC, e.enrolled_at DESC, cat.sort_order ASC, cat.name ASC'
        );
        $stmt->execute([$studentUserId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['id'];
            $out[$id] ??= [
                'id' => $id,
                'title' => $row['title'],
                'organisation_id' => (int) $row['organisation_id'],
                'organisation_name' => $row['organisation_name'],
                'requires_attachment' => !empty($row['requires_attachment']),
                'categories' => [],
            ];
            $out[$id]['categories'][(int) $row['category_id']] = $row['category_name'];
        }
        return $out;
    }

    /** @return array<int, int[]> provider user id => the given category ids it is approved for */
    public static function providersForCategories(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$categoryIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT provider_user_id, category_id FROM attachment_provider_audiences WHERE category_id IN ($placeholders) AND status = 'approved'"
        );
        $stmt->execute($categoryIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['provider_user_id']][] = (int) $row['category_id'];
        }
        return $out;
    }
}
