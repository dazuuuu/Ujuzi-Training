<?php

namespace App\Models;

use App\Core\Database;
use App\Models\StoreSetting;

/**
 * One attachee's assessment, filled by the branch admin where they are
 * attached:
 *  - the attachment organisation's own assessment form (each organisation
 *    providing attachment writes its questions once — its template — and
 *    its branch admins answer them for every student);
 *  - a daily log: each day the student comes, what they did, a score out of
 *    10 and remarks;
 *  - a closing remark when the attachment ends.
 * The grade is the form's marks (or, with no marked questions, the average
 * of the days). It must be closed before the attachment can be completed,
 * and is then shared with the organisation that runs the course — the grade
 * counts alongside the final exam.
 * Stored as items = {days: [...], answers: [...]} (older rows: a plain list).
 */
class AttachmentAssessment
{
    public const DAY_MAX = 10;

    /** Question kinds on a template: a mark out of max, a written answer, or yes / no. */
    public const KINDS = ['score' => 'Mark (out of)', 'text' => 'Written answer', 'yesno' => 'Yes / No'];

    /** The organisation providing attachment's own form: [question, kind, max]. */
    public static function template(int $providerUserId): array
    {
        $saved = json_decode((string) StoreSetting::get('attachment_assessment_template_' . $providerUserId, ''), true);
        return is_array($saved) ? $saved : [];
    }

    public static function saveTemplate(int $providerUserId, array $rows): array
    {
        $out = [];
        foreach (array_slice($rows, 0, 60) as $row) {
            $question = mb_substr(trim((string) ($row['question'] ?? '')), 0, 300);
            if ($question === '') {
                continue;
            }
            $kind = isset(self::KINDS[$row['kind'] ?? '']) ? $row['kind'] : 'score';
            $out[] = ['question' => $question, 'kind' => $kind, 'max' => $kind === 'score' ? max(1, min(1000, (int) ($row['max'] ?? 10))) : null];
        }
        StoreSetting::set('attachment_assessment_template_' . $providerUserId, json_encode($out, JSON_UNESCAPED_UNICODE));
        return $out;
    }

    /** [days, answers, old criteria rows] of a saved assessment. */
    public static function parts(?array $row): array
    {
        $items = $row['items'] ?? [];
        if (isset($items['days']) || isset($items['answers'])) {
            return [$items['days'] ?? [], $items['answers'] ?? [], []];
        }
        $days = array_values(array_filter($items, static fn($i): bool => is_array($i) && isset($i['date'])));
        $criteria = array_values(array_filter($items, static fn($i): bool => is_array($i) && isset($i['criterion'])));
        return [$days, [], $criteria];
    }

    /**
     * The form to fill for this attachee: the provider's current questions,
     * with the answers already given (matched by question). Questions already
     * answered but since removed from the template stay on the sheet.
     */
    public static function formFor(int $providerUserId, ?array $row): array
    {
        [, $answers] = self::parts($row);
        $given = [];
        foreach ($answers as $a) {
            $given[$a['question']] = $a;
        }
        $form = [];
        foreach (self::template($providerUserId) as $q) {
            $form[] = $q + ['value' => $given[$q['question']]['value'] ?? null];
            unset($given[$q['question']]);
        }
        foreach ($given as $a) {
            $form[] = $a;
        }
        return $form;
    }

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

    /** Closed = days recorded and a closing remark given. (Always true before the assessments update.) */
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

    public const NOT_MARKED = 'Fill the assessment form or the daily log, and write the closing remark, before completing the attachment — the log is sent to the organisation that runs the course.';

    /** A letter grade for a percent. */
    public static function grade(float $percent): string
    {
        return match (true) {
            $percent >= 80 => 'A',
            $percent >= 65 => 'B',
            $percent >= 50 => 'C',
            $percent >= 40 => 'D',
            default => 'E',
        };
    }

    /**
     * Saves the daily log. $rows: [date, activities, score (0–10), remarks].
     * It is closed (marked) once there are days and a closing remark.
     */
    public static function save(int $applicationId, array $rows, string $closingRemark, int $assessorId, array $form = [], array $answerValues = []): void
    {
        // The organisation's form: a snapshot of each question with its answer.
        $answers = [];
        $formGot = 0.0;
        $formMax = 0.0;
        foreach ($form as $i => $q) {
            $raw = $answerValues[$i] ?? null;
            $value = match ($q['kind']) {
                'score' => $raw === null || $raw === '' ? null : max(0, min((float) $q['max'], (float) $raw)),
                'yesno' => in_array($raw, ['yes', 'no'], true) ? $raw : null,
                default => ($t = mb_substr(trim((string) $raw), 0, 2000)) === '' ? null : $t,
            };
            $answers[] = ['question' => $q['question'], 'kind' => $q['kind'], 'max' => $q['max'], 'value' => $value];
            if ($q['kind'] === 'score' && $value !== null) {
                $formGot += $value;
                $formMax += (float) $q['max'];
            }
        }

        $items = [];
        $total = 0.0;
        foreach (array_slice($rows, 0, 366) as $row) {
            $date = (string) ($row['date'] ?? '');
            $activities = mb_substr(trim((string) ($row['activities'] ?? '')), 0, 1000);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ($activities === '' && ($row['score'] ?? '') === '')) {
                continue;
            }
            $score = max(0, min(self::DAY_MAX, (float) ($row['score'] ?? 0)));
            $items[] = ['date' => $date, 'activities' => $activities, 'score' => $score, 'remarks' => mb_substr(trim((string) ($row['remarks'] ?? '')), 0, 500)];
            $total += $score;
        }
        usort($items, static fn(array $a, array $b): int => strcmp($a['date'], $b['date']));
        $days = $items;
        $max = count($days) * self::DAY_MAX;
        // The grade: the form's marks when it has marked questions, else the days' average.
        if ($formMax > 0) {
            [$total, $max] = [$formGot, $formMax];
        }
        $items = ['days' => $days, 'answers' => $answers];
        $closingRemark = mb_substr(trim($closingRemark), 0, 3000);
        $closed = ($days || $formMax > 0 || array_filter($answers, static fn(array $a): bool => $a['value'] !== null)) && $closingRemark !== '';
        Database::connection()->prepare(
            'INSERT INTO attachment_assessments (application_id, items, total_score, max_score, percent, remarks, assessed_by_user_id, assessed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ' . ($closed ? 'NOW()' : 'NULL') . ')
             ON DUPLICATE KEY UPDATE items = VALUES(items), total_score = VALUES(total_score), max_score = VALUES(max_score),
                percent = VALUES(percent), remarks = VALUES(remarks), assessed_by_user_id = VALUES(assessed_by_user_id),
                assessed_at = ' . ($closed ? 'COALESCE(assessed_at, NOW())' : 'NULL')
        )->execute([$applicationId, json_encode($items, JSON_UNESCAPED_UNICODE), $total, $max, $max > 0 ? round($total * 100 / $max, 2) : 0, $closingRemark ?: null, $assessorId]);
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
