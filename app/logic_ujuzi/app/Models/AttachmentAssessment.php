<?php

namespace App\Models;

use App\Core\Database;
use App\Models\StoreSetting;

/**
 * The attachment organisation's assessment sheet for one attachee: criteria
 * with a maximum mark each, the marks given, comments and overall remarks.
 * It must be marked before the attachment can be completed, and is shared with
 * the organisation that runs the course when it is — their grades count
 * alongside the final exam.
 */
class AttachmentAssessment
{
    /** A ready-made sheet the organisation can start from and change. */
    public const STANDARD_CRITERIA = [
        ['criterion' => 'Punctuality and attendance', 'max' => 10],
        ['criterion' => 'Discipline and conduct', 'max' => 10],
        ['criterion' => 'Technical / practical skills for the course', 'max' => 20],
        ['criterion' => 'Quality of work', 'max' => 15],
        ['criterion' => 'Following safety rules and procedures', 'max' => 10],
        ['criterion' => 'Communication', 'max' => 10],
        ['criterion' => 'Teamwork', 'max' => 10],
        ['criterion' => 'Initiative and willingness to learn', 'max' => 15],
    ];

    public static function forApplication(int $applicationId): ?array
    {
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM attachment_assessments WHERE application_id = ? LIMIT 1');
            $stmt->execute([$applicationId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            return null; // before the assessments update
        }
        if (!$row) {
            return null;
        }
        $row['items'] = json_decode((string) $row['items'], true) ?: [];
        return $row;
    }

    /** Marked = at least one criterion has a mark. (Always true before the assessments update.) */
    public static function isMarked(int $applicationId): bool
    {
        try {
            Database::connection()->query('SELECT 1 FROM attachment_assessments LIMIT 1');
        } catch (\Throwable $e) {
            return true;
        }
        $sheet = self::forApplication($applicationId);
        return $sheet !== null && !empty($sheet['assessed_at']);
    }

    public const NOT_MARKED = 'Mark the assessment sheet before completing the attachment — it is sent to the organisation that runs the course.';

    /** The criteria this provider used last (or the standard sheet). */
    public static function criteriaFor(int $providerUserId): array
    {
        $saved = json_decode((string) StoreSetting::get('assessment_criteria_provider_' . $providerUserId, ''), true);
        return is_array($saved) && $saved ? $saved : self::STANDARD_CRITERIA;
    }

    /**
     * Saves the marked sheet. $rows: [criterion, max, score, comment]. The
     * criteria are remembered for this provider's next attachees.
     */
    public static function save(int $applicationId, int $providerUserId, array $rows, string $remarks, int $assessorId): void
    {
        $items = [];
        $total = 0.0;
        $max = 0.0;
        foreach (array_slice($rows, 0, 40) as $row) {
            $criterion = mb_substr(trim((string) ($row['criterion'] ?? '')), 0, 160);
            $outOf = max(1, min(1000, (float) ($row['max'] ?? 10)));
            if ($criterion === '') {
                continue;
            }
            $score = ($row['score'] ?? '') === '' ? null : max(0, min($outOf, (float) $row['score']));
            $items[] = ['criterion' => $criterion, 'max' => $outOf, 'score' => $score, 'comment' => mb_substr(trim((string) ($row['comment'] ?? '')), 0, 500)];
            $total += (float) $score;
            $max += $outOf;
        }
        $marked = (bool) array_filter($items, static fn(array $i): bool => $i['score'] !== null);
        Database::connection()->prepare(
            'INSERT INTO attachment_assessments (application_id, items, total_score, max_score, percent, remarks, assessed_by_user_id, assessed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ' . ($marked ? 'NOW()' : 'NULL') . ')
             ON DUPLICATE KEY UPDATE items = VALUES(items), total_score = VALUES(total_score), max_score = VALUES(max_score),
                percent = VALUES(percent), remarks = VALUES(remarks), assessed_by_user_id = VALUES(assessed_by_user_id),
                assessed_at = ' . ($marked ? 'NOW()' : 'NULL')
        )->execute([$applicationId, json_encode($items, JSON_UNESCAPED_UNICODE), $total, $max, $max > 0 ? round($total * 100 / $max, 2) : 0, mb_substr(trim($remarks), 0, 3000) ?: null, $assessorId]);
        StoreSetting::set('assessment_criteria_provider_' . $providerUserId, json_encode(array_map(
            static fn(array $i): array => ['criterion' => $i['criterion'], 'max' => $i['max']], $items
        ), JSON_UNESCAPED_UNICODE));
    }

    /** Sent to the organisation running the course (when the attachment is completed). */
    public static function share(int $applicationId): void
    {
        try {
            Database::connection()->prepare('UPDATE attachment_assessments SET shared_at = COALESCE(shared_at, NOW()) WHERE application_id = ?')
                ->execute([$applicationId]);
        } catch (\Throwable $e) {
            // Before the assessments update.
        }
    }

    /**
     * Shared assessments of students on this organisation's courses (optionally
     * only these students), newest first, with student, course and provider.
     */
    public static function sharedForOrganisation(int $organisationId, ?array $studentIds = null): array
    {
        $where = '';
        if ($studentIds !== null) {
            $ids = array_values(array_filter(array_map('intval', $studentIds)));
            $where = $ids ? ' AND a.student_user_id IN (' . implode(',', $ids) . ')' : ' AND 1 = 0';
        }
        try {
            $stmt = Database::connection()->prepare(
                "SELECT s.*, a.student_user_id, a.course_id, a.branch_id, c.title AS course_title,
                        u.first_name, u.other_names, u.last_name, u.registration_number, u.phone, u.email,
                        COALESCE(po.name, TRIM(CONCAT(p.first_name, ' ', p.last_name))) AS provider_name, b.title AS branch_title
                 FROM attachment_assessments s
                 INNER JOIN attachment_applications a ON a.id = s.application_id
                 INNER JOIN courses c ON c.id = a.course_id AND c.organisation_id = ?
                 INNER JOIN users u ON u.id = a.student_user_id
                 INNER JOIN users p ON p.id = a.provider_user_id
                 LEFT JOIN organisations po ON po.id = p.organisation_id
                 LEFT JOIN organisation_branches b ON b.id = a.branch_id
                 WHERE s.shared_at IS NOT NULL$where
                 ORDER BY s.shared_at DESC"
            );
            $stmt->execute([$organisationId]);
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static function (array $r): array {
            $r['items'] = json_decode((string) $r['items'], true) ?: [];
            $r['student_name'] = userFullName($r);
            return $r;
        }, $stmt->fetchAll());
    }

    /** The student's shared attachment grade for a course (percent), or null. */
    public static function gradeForStudentCourse(int $studentId, int $courseId): ?float
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT s.percent FROM attachment_assessments s INNER JOIN attachment_applications a ON a.id = s.application_id
                 WHERE a.student_user_id = ? AND a.course_id = ? AND s.shared_at IS NOT NULL LIMIT 1'
            );
            $stmt->execute([$studentId, $courseId]);
            $value = $stmt->fetchColumn();
            return $value === false ? null : (float) $value;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
