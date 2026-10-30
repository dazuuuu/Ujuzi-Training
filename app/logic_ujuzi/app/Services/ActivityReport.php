<?php

namespace App\Services;

use App\Core\Database;
use App\Models\AttachmentApplication;
use App\Models\OrganisationBranch;

/**
 * Super Admin → Reports → Organisation activity: everything an organisation
 * (and its branches) did, filtered by kind of organisation, organisation,
 * branch, status, finance status and dates — the same rows on the page, in
 * Excel and in the PDF.
 *
 * - Organisations providing courses: one row per student enrolled in one of
 *   its courses (enrolled, progress, completed, fees).
 * - Organisations providing attachment: one row per attachment request
 *   (requested, accepted, completed, the student's fees).
 */
class ActivityReport
{
    public const TYPES = ['course' => 'Organisations providing courses', 'attachment' => 'Organisations providing attachment'];

    public const STATUSES = [
        'course' => ['' => 'All statuses', 'in_progress' => 'In progress', 'completed' => 'Completed'],
        'attachment' => ['' => 'All statuses', 'pending' => 'Requested (waiting)', 'accepted' => 'Accepted (all)', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'rejected' => 'Rejected'],
    ];

    public const FINANCE = ['' => 'Any finance status', 'paid' => 'Fully paid', 'partial' => 'Partly paid', 'unpaid' => 'Not paid'];

    /** Reads and checks the filters from the query string. */
    public static function filters(array $query): array
    {
        $type = isset(self::TYPES[$query['type'] ?? '']) ? (string) $query['type'] : 'course';
        $status = (string) ($query['status'] ?? '');
        $finance = (string) ($query['finance'] ?? '');
        [$from, $to] = AttachmentReport::dateRange();
        $f = [
            'type' => $type,
            'org' => max(0, (int) ($query['org'] ?? 0)),
            'branch' => max(0, (int) ($query['branch'] ?? 0)),
            'status' => isset(self::STATUSES[$type][$status]) ? $status : '',
            'finance' => isset(self::FINANCE[$finance]) ? $finance : '',
            'from' => $from,
            'to' => $to,
        ];
        $orgs = self::organisations($type);
        if (!isset($orgs[$f['org']])) {
            $f['org'] = 0;
        }
        $branchIds = array_map(static fn(array $b): int => (int) $b['id'], self::branches($type, $f['org']));
        if (!in_array($f['branch'], $branchIds, true)) {
            $f['branch'] = 0;
        }
        return $f;
    }

    /** Organisations of this kind: [id => name]. For attachment the id is the provider's user id. */
    public static function organisations(string $type): array
    {
        $sql = $type === 'attachment'
            ? "SELECT u.id, COALESCE(NULLIF(o.name, ''), TRIM(CONCAT(u.first_name, ' ', u.last_name)), u.email) AS name
               FROM users u INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'attachment_trainer'
               LEFT JOIN organisations o ON o.id = u.organisation_id ORDER BY name"
            : "SELECT o.id, o.name FROM organisations o
               WHERE EXISTS (SELECT 1 FROM courses c WHERE c.organisation_id = o.id)
                  OR EXISTS (SELECT 1 FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.organisation_id = o.id AND r.slug = 'organisation_admin')
               ORDER BY o.name";
        $out = [];
        foreach (Database::connection()->query($sql)->fetchAll() as $row) {
            $out[(int) $row['id']] = (string) $row['name'];
        }
        return $out;
    }

    public static function branches(string $type, int $orgId): array
    {
        if ($orgId < 1) {
            return [];
        }
        return $type === 'attachment' ? OrganisationBranch::forAttachmentProvider($orgId) : OrganisationBranch::forOrganisation($orgId);
    }

    /** @return array{title: string, subtitle: string, headers: string[], rows: array, totals: array, summary: array} */
    public static function build(array $f): array
    {
        $report = $f['type'] === 'attachment' ? self::attachment($f) : self::course($f);
        $orgName = $f['org'] ? (self::organisations($f['type'])[$f['org']] ?? '') : 'All organisations';
        $branchName = 'all branches';
        foreach (self::branches($f['type'], $f['org']) as $b) {
            if ((int) $b['id'] === $f['branch']) {
                $branchName = (string) $b['title'];
            }
        }
        $parts = [self::TYPES[$f['type']], $orgName . ' (' . $branchName . ')'];
        if ($f['status'] !== '') {
            $parts[] = self::STATUSES[$f['type']][$f['status']];
        }
        if ($f['finance'] !== '') {
            $parts[] = self::FINANCE[$f['finance']];
        }
        if ($f['from'] !== '' || $f['to'] !== '') {
            $parts[] = ($f['from'] ?: 'start') . ' to ' . ($f['to'] ?: date('Y-m-d'));
        }
        $report['title'] = 'Activity — ' . $orgName;
        $report['subtitle'] = implode(' · ', $parts);
        return $report;
    }

    private static function dateTime(?string $at): string
    {
        return $at ? date('Y-m-d H:i', strtotime($at)) : '';
    }

    private static function financeStatus(float $fee, float $paid): string
    {
        if ($fee <= 0 || $paid >= $fee - 0.009) {
            return 'Paid';
        }
        return $paid > 0 ? 'Partly paid' : 'Not paid';
    }

    private static function financeMatches(string $filter, string $status): bool
    {
        return $filter === '' || ['paid' => 'Paid', 'partial' => 'Partly paid', 'unpaid' => 'Not paid'][$filter] === $status;
    }

    private static function dateWhere(string $column, array $f, array &$params): string
    {
        $sql = '';
        if ($f['from'] !== '') {
            $sql .= " AND $column >= ?";
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $sql .= " AND $column < ?";
            $params[] = date('Y-m-d', strtotime($f['to'] . ' +1 day'));
        }
        return $sql;
    }

    private static function course(array $f): array
    {
        $params = [];
        $where = '';
        if ($f['org']) {
            $where .= ' AND c.organisation_id = ?';
            $params[] = $f['org'];
        }
        // The student's branch at the organisation, else the branch offering the course.
        $branchSql = 'COALESCE(sm.branch_id, c.branch_id)';
        if ($f['branch']) {
            $where .= " AND $branchSql = ?";
            $params[] = $f['branch'];
        }
        $where .= self::dateWhere('COALESCE(e.enrolled_at, e.created_at)', $f, $params);
        $stmt = Database::connection()->prepare(
            "SELECT e.user_id, e.course_id, e.payment_status, COALESCE(e.enrolled_at, e.created_at) AS enrolled_at,
                    u.registration_number, u.first_name, u.other_names, u.last_name, u.phone, u.email,
                    c.title, c.course_code, c.enrollment_fee_ksh AS fee, o.name AS org_name, b.title AS branch_title,
                    CONCAT(t.first_name, ' ', t.last_name) AS tutor,
                    (SELECT COUNT(*) FROM course_modules m WHERE m.course_id = c.id) AS modules_total,
                    (SELECT COUNT(*) FROM course_module_progress p INNER JOIN course_modules m ON m.id = p.module_id
                       WHERE p.user_id = e.user_id AND p.course_id = c.id AND p.passed = 1) AS modules_passed,
                    (SELECT cc.completed_at FROM course_completions cc WHERE cc.user_id = e.user_id AND cc.course_id = c.id LIMIT 1) AS completed_at,
                    (SELECT f.completed_at FROM course_final_exam_progress f WHERE f.user_id = e.user_id AND f.course_id = c.id AND f.passed = 1 LIMIT 1) AS final_passed_at,
                    COALESCE((SELECT SUM(t2.amount_ksh) FROM wallet_transactions t2
                              WHERE t2.user_id = e.user_id AND t2.course_id = c.id AND t2.type = 'course_payment' AND t2.archived_at IS NULL), 0) AS paid
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id
             INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             LEFT JOIN users t ON t.id = c.trainer_user_id
             LEFT JOIN organisation_memberships sm ON sm.id = (
                 SELECT m2.id FROM organisation_memberships m2
                 WHERE m2.user_id = e.user_id AND m2.organisation_id = c.organisation_id AND m2.status = 'approved'
                 ORDER BY m2.id DESC LIMIT 1)
             LEFT JOIN organisation_branches b ON b.id = $branchSql
             WHERE 1 = 1$where
             ORDER BY COALESCE(e.enrolled_at, e.created_at) DESC"
        );
        $stmt->execute($params);

        $rows = [];
        $summary = ['Students' => 0, 'Completed' => 0, 'In progress' => 0, 'Paid' => 0, 'Partly paid' => 0, 'Not paid' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $fee = (float) $r['fee'];
            $paid = $r['payment_status'] === 'paid' ? $fee : min($fee, (float) $r['paid']);
            $finance = self::financeStatus($fee, $paid);
            $completedAt = $r['completed_at'] ?: $r['final_passed_at'];
            $status = $completedAt ? 'Completed' : 'In progress';
            if (($f['status'] === 'completed' && !$completedAt) || ($f['status'] === 'in_progress' && $completedAt) || !self::financeMatches($f['finance'], $finance)) {
                continue;
            }
            $total = (int) $r['modules_total'];
            $progress = $completedAt ? 100 : ($total > 0 ? (int) round(min((int) $r['modules_passed'], $total) * 100 / $total) : 0);
            $rows[] = [
                self::dateTime($r['enrolled_at']),
                trim(preg_replace('/\s+/', ' ', $r['first_name'] . ' ' . $r['other_names'] . ' ' . $r['last_name'])),
                (string) $r['registration_number'],
                (string) ($r['phone'] ?: $r['email']),
                (string) $r['title'] . ($r['course_code'] ? ' (' . $r['course_code'] . ')' : ''),
                trim((string) $r['tutor']),
                (string) $r['org_name'],
                (string) ($r['branch_title'] ?: 'Main / no branch'),
                $progress,
                $status,
                self::dateTime($completedAt),
                round($fee, 2),
                round($paid, 2),
                round(max(0, $fee - $paid), 2),
                $finance,
            ];
            $summary['Students']++;
            $summary[$status]++;
            $summary[$finance]++;
        }
        return [
            'headers' => ['Enrolled (date & time)', 'Student', 'Reg. no.', 'Contact', 'Course', 'Tutor', 'Organisation', 'Branch', 'Progress %', 'Status', 'Completed (date & time)', 'Fee (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)', 'Finance status'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' students', '', '', '', '', '', '', '', '', '', array_sum(array_column($rows, 11)), array_sum(array_column($rows, 12)), array_sum(array_column($rows, 13)), ''],
            'summary' => $summary + ['Fees (Ksh)' => array_sum(array_column($rows, 11)), 'Collected (Ksh)' => array_sum(array_column($rows, 12)), 'Outstanding (Ksh)' => array_sum(array_column($rows, 13))],
            'statusColumn' => 9,
            'financeColumn' => 14,
        ];
    }

    private static function attachment(array $f): array
    {
        $query = ['from' => $f['from'], 'to' => $f['to']];
        $query['status'] = match ($f['status']) {
            'accepted' => 'ongoing_or_completed',
            'ongoing' => 'ongoing',
            'completed' => AttachmentApplication::STATUS_COMPLETED,
            'pending' => AttachmentApplication::STATUS_PENDING,
            'rejected' => AttachmentApplication::STATUS_REJECTED,
            default => '',
        };
        if ($f['org']) {
            $query['provider_id'] = $f['org'];
        }
        if ($f['branch']) {
            $query['branch_ids'] = [$f['branch']];
        }
        $applications = AttachmentApplication::report($query);
        $fees = WalletService::feeSummaries(array_column($applications, 'student_user_id'));

        $rows = [];
        $summary = ['Requests' => 0, 'Waiting' => 0, 'Ongoing' => 0, 'Completed' => 0, 'Rejected' => 0, 'Paid' => 0, 'Partly paid' => 0, 'Not paid' => 0];
        foreach ($applications as $a) {
            $fee = (float) ($fees[(int) $a['student_user_id']]['fee'] ?? 0);
            $paid = (float) ($fees[(int) $a['student_user_id']]['paid'] ?? 0);
            $finance = self::financeStatus($fee, $paid);
            if (!self::financeMatches($f['finance'], $finance)) {
                continue;
            }
            $bucket = match ((string) $a['status']) {
                'pending' => 'Waiting',
                'accepted', 'paused' => 'Ongoing',
                'completed', 'recommended' => 'Completed',
                default => 'Rejected',
            };
            $rows[] = [
                self::dateTime($a['selected_at'] ?? $a['created_at'] ?? null),
                trim(preg_replace('/\s+/', ' ', $a['first_name'] . ' ' . ($a['other_names'] ?? '') . ' ' . $a['last_name'])),
                (string) ($a['registration_number'] ?? ''),
                (string) (($a['phone'] ?? '') ?: $a['email']),
                (string) (($a['course_title'] ?? '') ?: ($a['category_name'] ?? '')),
                (string) (($a['course_organisation_name'] ?? '') ?: ($a['category_organisation_name'] ?? '')),
                (string) ($a['provider_organisation_name'] ?: trim($a['provider_first_name'] . ' ' . $a['provider_last_name'])),
                (string) (($a['branch_title'] ?? '') ?: 'No branch'),
                AttachmentApplication::statusLabel((string) $a['status']),
                self::dateTime($a['accepted_at'] ?? null),
                self::dateTime($a['recommended_at'] ?? ($a['completed_at'] ?? null)),
                round($fee, 2),
                round($paid, 2),
                round(max(0, $fee - $paid), 2),
                $finance,
            ];
            $summary['Requests']++;
            $summary[$bucket]++;
            $summary[$finance]++;
        }
        return [
            'headers' => ['Requested (date & time)', 'Student', 'Reg. no.', 'Contact', 'Course', 'Organisation providing courses', 'Organisation providing attachment', 'Branch', 'Status', 'Accepted (date & time)', 'Completed (date & time)', 'Fees (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)', 'Finance status'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' requests', '', '', '', '', '', '', '', '', '', array_sum(array_column($rows, 11)), array_sum(array_column($rows, 12)), array_sum(array_column($rows, 13)), ''],
            'summary' => $summary + ['Fees (Ksh)' => array_sum(array_column($rows, 11)), 'Collected (Ksh)' => array_sum(array_column($rows, 12)), 'Outstanding (Ksh)' => array_sum(array_column($rows, 13))],
            'statusColumn' => 8,
            'financeColumn' => 14,
        ];
    }
}
