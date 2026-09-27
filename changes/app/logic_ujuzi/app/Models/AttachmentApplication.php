<?php

namespace App\Models;

use App\Core\Database;

class AttachmentApplication
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_RECOMMENDED = 'recommended';
    public const STATUS_REJECTED = 'rejected';

    public static function saveSelection(int $studentUserId, int $courseId, int $providerUserId, int $branchId): void
    {
        Database::connection()->prepare(
            'INSERT INTO attachment_applications (student_user_id, course_id, provider_user_id, branch_id, status, selected_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                provider_user_id = VALUES(provider_user_id),
                branch_id = VALUES(branch_id),
                status = IF(status IN (\'recommended\', \'completed\'), status, VALUES(status)),
                selected_at = NOW(),
                accepted_at = IF(status IN (\'accepted\', \'completed\', \'recommended\'), accepted_at, NULL),
                completed_at = IF(status IN (\'completed\', \'recommended\'), completed_at, NULL),
                recommended_at = IF(status = \'recommended\', recommended_at, NULL)'
        )->execute([$studentUserId, $courseId, $providerUserId, $branchId > 0 ? $branchId : null, self::STATUS_PENDING]);
    }

    /** The student's own attachment request (not tied to any course), with provider and branch details. */
    public static function forStudent(int $studentUserId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.first_name, u.last_name, u.email, o.name AS organisation_name,
                    b.title AS branch_title, b.location AS branch_location
             FROM attachment_applications a
             INNER JOIN users u ON u.id = a.provider_user_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             WHERE a.student_user_id = ? AND a.course_id IS NULL
             LIMIT 1'
        );
        $stmt->execute([$studentUserId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** Creates or re-submits the student's request. Returns false once it has been accepted/completed. */
    public static function saveGeneralSelection(int $studentUserId, int $providerUserId, ?int $branchId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, status FROM attachment_applications WHERE student_user_id = ? AND course_id IS NULL LIMIT 1');
        $stmt->execute([$studentUserId]);
        $existing = $stmt->fetch();
        if ($existing) {
            if (!in_array($existing['status'], [self::STATUS_PENDING, self::STATUS_REJECTED], true)) {
                return false;
            }
            $pdo->prepare(
                "UPDATE attachment_applications
                 SET provider_user_id = ?, branch_id = ?, status = 'pending', selected_at = NOW(),
                     accepted_at = NULL, completed_at = NULL, recommended_at = NULL, rejected_at = NULL
                 WHERE id = ?"
            )->execute([$providerUserId, $branchId, (int) $existing['id']]);
            return true;
        }
        $pdo->prepare(
            'INSERT INTO attachment_applications (student_user_id, course_id, provider_user_id, branch_id, status, selected_at)
             VALUES (?, NULL, ?, ?, ?, NOW())'
        )->execute([$studentUserId, $providerUserId, $branchId, self::STATUS_PENDING]);
        return true;
    }

    /**
     * Files the student's request for one course category. A student gets one
     * request per category: returns false while one is pending, accepted or
     * completed. Only a declined request can be replaced.
     */
    public static function saveCategorySelection(int $studentUserId, int $providerUserId, ?int $branchId, int $categoryId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, status FROM attachment_applications WHERE student_user_id = ? AND category_id = ? LIMIT 1');
        $stmt->execute([$studentUserId, $categoryId]);
        $existing = $stmt->fetch();
        if ($existing) {
            if ($existing['status'] !== self::STATUS_REJECTED) {
                return false;
            }
            $pdo->prepare(
                "UPDATE attachment_applications
                 SET provider_user_id = ?, branch_id = ?, status = 'pending', selected_at = NOW(),
                     accepted_at = NULL, completed_at = NULL, recommended_at = NULL, rejected_at = NULL, provider_note = NULL
                 WHERE id = ?"
            )->execute([$providerUserId, $branchId, (int) $existing['id']]);
            return true;
        }
        $pdo->prepare(
            'INSERT INTO attachment_applications (student_user_id, course_id, category_id, provider_user_id, branch_id, status, selected_at)
             VALUES (?, NULL, ?, ?, ?, ?, NOW())'
        )->execute([$studentUserId, $categoryId, $providerUserId, $branchId, self::STATUS_PENDING]);
        return true;
    }

    /** Withdraws the student's own request while it is still pending. Returns false otherwise. */
    public static function cancelPending(int $id, int $studentUserId): bool
    {
        $stmt = Database::connection()->prepare(
            "DELETE FROM attachment_applications WHERE id = ? AND student_user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$id, $studentUserId]);
        return $stmt->rowCount() > 0;
    }

    /** Every attachment request the student has made, newest first, with provider, branch and category. */
    public static function allForStudent(int $studentUserId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.first_name, u.last_name, u.email, o.name AS organisation_name,
                    b.title AS branch_title, b.location AS branch_location,
                    cat.name AS category_name, co.name AS category_organisation_name
             FROM attachment_applications a
             INNER JOIN users u ON u.id = a.provider_user_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             LEFT JOIN organisation_categories cat ON cat.id = a.category_id
             LEFT JOIN organisations co ON co.id = cat.organisation_id
             WHERE a.student_user_id = ? AND a.course_id IS NULL
             ORDER BY a.selected_at DESC'
        );
        $stmt->execute([$studentUserId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function findForStudent(int $id, int $studentUserId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, c.title AS course_title, u.first_name, u.last_name, u.email, o.name AS organisation_name,
                    b.title AS branch_title, b.location AS branch_location, cat.name AS category_name
             FROM attachment_applications a
             LEFT JOIN courses c ON c.id = a.course_id
             INNER JOIN users u ON u.id = a.provider_user_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             LEFT JOIN organisation_categories cat ON cat.id = a.category_id
             WHERE a.id = ? AND a.student_user_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $studentUserId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function forStudentCourse(int $studentUserId, int $courseId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, c.title AS course_title, u.first_name, u.last_name, u.email,
                    o.name AS organisation_name,
                    b.title AS branch_title, b.location AS branch_location
             FROM attachment_applications a
             LEFT JOIN courses c ON c.id = a.course_id
             INNER JOIN users u ON u.id = a.provider_user_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             WHERE a.student_user_id = ? AND a.course_id = ?
             LIMIT 1'
        );
        $stmt->execute([$studentUserId, $courseId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function forProvider(int $providerUserId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, c.title AS course_title, s.first_name, s.last_name, s.email, s.phone,
                    r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name,
                    b.title AS branch_title, b.location AS branch_location, cat.name AS category_name
             FROM attachment_applications a
             LEFT JOIN courses c ON c.id = a.course_id
             LEFT JOIN organisation_categories cat ON cat.id = a.category_id
             INNER JOIN users s ON s.id = a.student_user_id
             INNER JOIN roles r ON r.id = s.role_id
             LEFT JOIN organisations o ON o.id = s.organisation_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             WHERE a.provider_user_id = ?
             ORDER BY FIELD(a.status, \'pending\', \'paused\', \'accepted\', \'completed\', \'recommended\', \'rejected\'), a.selected_at DESC'
        );
        $stmt->execute([$providerUserId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forBranch(int $branchId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, c.title AS course_title, s.first_name, s.last_name, s.email, s.phone,
                    b.title AS branch_title, b.location AS branch_location,
                    cat.name AS category_name, co.name AS category_organisation_name
             FROM attachment_applications a
             LEFT JOIN courses c ON c.id = a.course_id
             INNER JOIN users s ON s.id = a.student_user_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             LEFT JOIN organisation_categories cat ON cat.id = a.category_id
             LEFT JOIN organisations co ON co.id = cat.organisation_id
             WHERE a.branch_id = ?
             ORDER BY FIELD(a.status, \'pending\', \'paused\', \'accepted\', \'completed\', \'recommended\', \'rejected\'), a.selected_at DESC'
        );
        $stmt->execute([$branchId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function findForBranch(int $id, int $branchId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM attachment_applications WHERE id = ? AND branch_id = ? LIMIT 1');
        $stmt->execute([$id, $branchId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::connection()->query(
            "SELECT a.*, c.title AS course_title, s.first_name, s.last_name, s.email,
                    p.first_name AS provider_first_name, p.last_name AS provider_last_name, p.email AS provider_email,
                    b.title AS branch_title
             FROM attachment_applications a
             LEFT JOIN courses c ON c.id = a.course_id
             INNER JOIN users s ON s.id = a.student_user_id
             INNER JOIN users p ON p.id = a.provider_user_id
             LEFT JOIN organisation_branches b ON b.id = a.branch_id
             ORDER BY a.updated_at DESC
             LIMIT {$limit}"
        );
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function findForProvider(int $id, int $providerUserId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM attachment_applications WHERE id = ? AND provider_user_id = ? LIMIT 1');
        $stmt->execute([$id, $providerUserId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** How a status reads to people: students, reviewers and Super Admin see the same words. */
    public static function statusLabel(string $status): string
    {
        return [
            self::STATUS_PENDING => 'Requested',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_PAUSED => 'On hold',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_RECOMMENDED => 'Completed',
            self::STATUS_REJECTED => 'Declined',
        ][$status] ?? ucfirst($status);
    }

    /** One request with everything a reviewer or Super Admin needs to see: student, provider, branch and category. */
    public static function findDetailed(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::detailSql() . ' WHERE a.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /**
     * The request, if $user may review it: the admin of its branch, or the
     * provider itself when the request isn't for a branch.
     */
    public static function findForReviewer(int $id, array $user): ?array
    {
        $application = self::findDetailed($id);
        if (!$application || empty($user['id'])) {
            return null;
        }
        $role = (string) ($user['role_slug'] ?? '');
        if ($role === 'branch_admin' && $application['branch_id']) {
            foreach (OrganisationBranch::forBranchAdmin((int) $user['id']) as $branch) {
                if ((int) $branch['id'] === $application['branch_id']) {
                    return $application;
                }
            }
        }
        if ($role === 'attachment_trainer' && !$application['branch_id'] && $application['provider_user_id'] === (int) $user['id']) {
            return $application;
        }
        return null;
    }

    public static function markLetterSent(int $id): void
    {
        Database::connection()->prepare('UPDATE attachment_applications SET letter_sent_at = NOW() WHERE id = ?')->execute([$id]);
    }

    /**
     * Every request, for Super Admin. $status filters by one status
     * ('completed' covers completed and recommended); $query matches the
     * student, provider, branch or category; $from / $to (Y-m-d) limit the
     * date the student sent the request, inclusive.
     */
    public static function adminList(string $status = '', string $query = '', string $from = '', string $to = ''): array
    {
        return self::report(['status' => $status, 'q' => $query, 'from' => $from, 'to' => $to]);
    }

    /**
     * Requests with full detail, filtered. Keys: status, q, from, to (as in
     * adminList), branch_ids (int[]: only these branches) and provider_id
     * (only this provider). Used by the Super Admin list, the reviewers'
     * date filters, and every Excel download.
     */
    public static function report(array $filters): array
    {
        $where = ['a.course_id IS NULL'];
        $params = [];
        $status = (string) ($filters['status'] ?? '');
        if ($status === self::STATUS_COMPLETED) {
            $where[] = "a.status IN ('completed', 'recommended')";
        } elseif (in_array($status, [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_PAUSED, self::STATUS_REJECTED], true)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }
        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $like = '%' . $query . '%';
            $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?
                        OR po.name LIKE ? OR p.first_name LIKE ? OR b.title LIKE ? OR cat.name LIKE ? OR co.name LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        foreach (['from' => '>=', 'to' => '<'] as $key => $op) {
            $date = (string) ($filters[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $where[] = "a.selected_at $op ?";
                $params[] = $key === 'to' ? date('Y-m-d', strtotime($date . ' +1 day')) : $date;
            }
        }
        if (array_key_exists('branch_ids', $filters)) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['branch_ids'])));
            if (!$ids) {
                return [];
            }
            $where[] = 'a.branch_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            array_push($params, ...$ids);
        }
        if (!empty($filters['provider_id'])) {
            $where[] = 'a.provider_user_id = ?';
            $params[] = (int) $filters['provider_id'];
        }
        $stmt = Database::connection()->prepare(
            self::detailSql() . ' WHERE ' . implode(' AND ', $where)
            . " ORDER BY FIELD(a.status, 'pending', 'paused', 'accepted', 'completed', 'recommended', 'rejected'), a.selected_at DESC"
        );
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    private static function detailSql(): string
    {
        return 'SELECT a.*, s.first_name, s.last_name, s.email, s.phone,
                       p.first_name AS provider_first_name, p.last_name AS provider_last_name, p.email AS provider_email,
                       po.name AS provider_organisation_name,
                       b.title AS branch_title, b.location AS branch_location,
                       cat.name AS category_name, co.name AS category_organisation_name
                FROM attachment_applications a
                INNER JOIN users s ON s.id = a.student_user_id
                INNER JOIN users p ON p.id = a.provider_user_id
                LEFT JOIN organisations po ON po.id = p.organisation_id
                LEFT JOIN organisation_branches b ON b.id = a.branch_id
                LEFT JOIN organisation_categories cat ON cat.id = a.category_id
                LEFT JOIN organisations co ON co.id = cat.organisation_id';
    }

    public static function setStatus(int $id, string $status, ?string $note = null): void
    {
        $allowed = [self::STATUS_ACCEPTED, self::STATUS_PAUSED, self::STATUS_COMPLETED, self::STATUS_RECOMMENDED, self::STATUS_REJECTED];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid attachment status.');
        }
        $column = [
            self::STATUS_ACCEPTED => 'accepted_at',
            self::STATUS_PAUSED => 'paused_at',
            self::STATUS_COMPLETED => 'completed_at',
            self::STATUS_RECOMMENDED => 'recommended_at',
            self::STATUS_REJECTED => 'rejected_at',
        ][$status];
        Database::connection()->prepare(
            "UPDATE attachment_applications SET status = ?, provider_note = ?, {$column} = NOW() WHERE id = ?"
        )->execute([$status, $note, $id]);
    }

    public static function hasCompletedAttachment(int $studentUserId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM attachment_applications WHERE student_user_id = ? AND status = 'recommended' LIMIT 1"
        );
        $stmt->execute([$studentUserId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function certifiableCourseIdsForStudent(int $studentUserId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT course_id FROM attachment_applications
             WHERE student_user_id = ? AND status = 'recommended'"
        );
        $stmt->execute([$studentUserId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function countByStatus(): array
    {
        $rows = Database::connection()->query(
            'SELECT status, COUNT(*) AS total FROM attachment_applications GROUP BY status'
        )->fetchAll();
        $out = [
            self::STATUS_PENDING => 0,
            self::STATUS_ACCEPTED => 0,
            self::STATUS_PAUSED => 0,
            self::STATUS_COMPLETED => 0,
            self::STATUS_RECOMMENDED => 0,
            self::STATUS_REJECTED => 0,
        ];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['total'];
        }
        return $out;
    }

    public static function hydrate(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['student_user_id'] = (int) ($row['student_user_id'] ?? 0);
        $row['course_id'] = !empty($row['course_id']) ? (int) $row['course_id'] : null;
        $row['category_id'] = !empty($row['category_id']) ? (int) $row['category_id'] : null;
        $row['provider_user_id'] = (int) ($row['provider_user_id'] ?? 0);
        $row['branch_id'] = !empty($row['branch_id']) ? (int) $row['branch_id'] : 0;
        return $row;
    }
}
