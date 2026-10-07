<?php

namespace App\Services;

use App\Core\Database;

/**
 * The numbers behind the visual reports, scoped to who is looking:
 * everything (Super Admin), one organisation providing courses
 * ('organisation' => id), or one attachment provider ('provider' => user id).
 *
 * Every chart is ['title', 'type' => bar|line|hbar|donut, 'unit' => ''|'Ksh',
 * 'points' => [label => number]]. A part that fails (e.g. before a migration)
 * is left out rather than breaking the page.
 */
class ReportCharts
{
    public static function build(array $scope, string $from = '', string $to = ''): array
    {
        $orgId = (int) ($scope['organisation'] ?? 0);
        $providerId = (int) ($scope['provider'] ?? 0);
        // A branch admin's own slice: their branch's students, or their branch's attachees.
        $studentIds = isset($scope['students']) ? (array_values(array_filter(array_map('intval', $scope['students']))) ?: [0]) : null;
        $branchIds = isset($scope['branches']) ? (array_values(array_filter(array_map('intval', $scope['branches']))) ?: [0]) : null;
        $onlyAttachments = $branchIds !== null;
        $charts = [];
        $add = static function (string $key, callable $fn) use (&$charts): void {
            try {
                $chart = $fn();
                if ($chart) {
                    $charts[$key] = $chart;
                }
            } catch (\Throwable $e) {
                // Skip a chart whose data can't be read.
            }
        };

        if (!$providerId && !$onlyAttachments) {
            $courseWhere = $orgId ? ' AND c.organisation_id = ' . $orgId : '';
            $enrolWho = $studentIds !== null ? ' AND e.user_id IN (' . implode(',', $studentIds) . ')' : '';
            $payWho = $studentIds !== null ? ' AND t.user_id IN (' . implode(',', $studentIds) . ')' : '';
            $add('enrolments', static fn() => [
                'title' => 'Enrolments per month', 'type' => 'bar', 'unit' => '',
                'points' => self::perMonth("SELECT DATE_FORMAT(e.enrolled_at, '%Y-%m') ym, COUNT(*) n FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id WHERE 1=1$courseWhere$enrolWho" . self::range('e.enrolled_at', $from, $to) . ' GROUP BY ym', $from, $to),
            ]);
            $add('payments', static fn() => [
                'title' => 'Course payments per month', 'type' => 'line', 'unit' => 'Ksh',
                'points' => self::perMonth("SELECT DATE_FORMAT(t.created_at, '%Y-%m') ym, SUM(t.amount_ksh) n FROM wallet_transactions t INNER JOIN courses c ON c.id = t.course_id WHERE t.type = 'course_payment'$courseWhere$payWho" . self::range('t.created_at', $from, $to) . ' GROUP BY ym', $from, $to),
            ]);
            $add('by_course', static fn() => [
                'title' => 'Students per course', 'type' => 'hbar', 'unit' => '',
                'points' => self::pairs("SELECT c.title, COUNT(*) n FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id WHERE 1=1$courseWhere$enrolWho" . self::range('e.enrolled_at', $from, $to) . ' GROUP BY c.id, c.title ORDER BY n DESC LIMIT 8'),
            ]);
            $add('collected', static fn() => [
                'title' => 'Fees collected per course', 'type' => 'hbar', 'unit' => 'Ksh',
                'points' => self::pairs("SELECT c.title, SUM(t.amount_ksh) n FROM wallet_transactions t INNER JOIN courses c ON c.id = t.course_id WHERE t.type = 'course_payment'$courseWhere$payWho" . self::range('t.created_at', $from, $to) . ' GROUP BY c.id, c.title ORDER BY n DESC LIMIT 8'),
            ]);
            $add('completion', static function () use ($courseWhere, $enrolWho, $from, $to): array {
                $total = (int) self::value("SELECT COUNT(*) FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id WHERE 1=1$courseWhere$enrolWho" . self::range('e.enrolled_at', $from, $to));
                $done = (int) self::value("SELECT COUNT(*) FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id INNER JOIN course_final_exam_progress f ON f.user_id = e.user_id AND f.course_id = e.course_id AND f.passed = 1 WHERE 1=1$courseWhere$enrolWho" . self::range('e.enrolled_at', $from, $to));
                return ['title' => 'Course completion', 'type' => 'donut', 'unit' => '',
                        'points' => ['Completed' => $done, 'In progress' => max(0, $total - $done)]];
            });
        }

        $attachWhere = $providerId ? ' AND a.provider_user_id = ' . $providerId
            : ($orgId ? ' AND a.course_id IN (SELECT id FROM courses WHERE organisation_id = ' . $orgId . ')' : '');
        if ($studentIds !== null) {
            $attachWhere .= ' AND a.student_user_id IN (' . implode(',', $studentIds) . ')';
        }
        if ($branchIds !== null) {
            $attachWhere .= ' AND a.branch_id IN (' . implode(',', $branchIds) . ')';
        }
        $add('attachment_status', static function () use ($attachWhere, $from, $to): array {
            $labels = ['pending' => 'Requested', 'paused' => 'On hold', 'accepted' => 'Accepted', 'recommended' => 'Completed', 'completed' => 'Completed', 'rejected' => 'Declined'];
            $points = ['Requested' => 0, 'On hold' => 0, 'Accepted' => 0, 'Completed' => 0, 'Declined' => 0];
            foreach (self::pairs("SELECT a.status, COUNT(*) n FROM attachment_applications a WHERE 1=1$attachWhere" . self::range('a.selected_at', $from, $to) . ' GROUP BY a.status') as $status => $n) {
                $points[$labels[$status] ?? ucfirst($status)] = ($points[$labels[$status] ?? ucfirst($status)] ?? 0) + $n;
            }
            return ['title' => 'Attachment requests by status', 'type' => 'donut', 'unit' => '', 'points' => $points];
        });
        $add('attachment_months', static fn() => [
            'title' => 'Attachment requests per month', 'type' => 'bar', 'unit' => '',
            'points' => self::perMonth("SELECT DATE_FORMAT(a.selected_at, '%Y-%m') ym, COUNT(*) n FROM attachment_applications a WHERE 1=1$attachWhere" . self::range('a.selected_at', $from, $to) . ' GROUP BY ym', $from, $to),
        ]);
        if ($providerId || (!$orgId && !$onlyAttachments)) {
            $add('attachment_branches', static fn() => [
                'title' => 'Attachees per branch', 'type' => 'hbar', 'unit' => '',
                'points' => self::pairs("SELECT COALESCE(b.title, 'No branch') t, COUNT(*) n FROM attachment_applications a LEFT JOIN organisation_branches b ON b.id = a.branch_id WHERE a.status <> 'rejected'$attachWhere" . self::range('a.selected_at', $from, $to) . ' GROUP BY t ORDER BY n DESC LIMIT 8'),
            ]);
        }
        $add('attachment_courses', static fn() => [
            'title' => 'Attachment requests per course', 'type' => 'hbar', 'unit' => '',
            'points' => self::pairs("SELECT COALESCE(c.title, 'Not tied to a course') t, COUNT(*) n FROM attachment_applications a LEFT JOIN courses c ON c.id = a.course_id WHERE 1=1$attachWhere" . self::range('a.selected_at', $from, $to) . ' GROUP BY t ORDER BY n DESC LIMIT 8'),
        ]);

        if (!$orgId && !$providerId && !$onlyAttachments && $studentIds === null) {
            $add('students', static fn() => [
                'title' => 'New students per month', 'type' => 'bar', 'unit' => '',
                'points' => self::perMonth("SELECT DATE_FORMAT(u.created_at, '%Y-%m') ym, COUNT(*) n FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'student'" . self::range('u.created_at', $from, $to) . ' GROUP BY ym', $from, $to),
            ]);
        }
        return $charts;
    }

    /** All charts as rows for one Excel sheet: chart, label, value. */
    public static function rows(array $charts): array
    {
        $rows = [];
        foreach ($charts as $chart) {
            foreach ($chart['points'] as $label => $value) {
                $rows[] = [$chart['title'], (string) $label, $chart['unit'] === 'Ksh' ? round((float) $value, 2) : (int) $value];
            }
        }
        return $rows;
    }

    /** Month label => number for the chosen range (default: the last 12 months), zero-filled. */
    private static function perMonth(string $sql, string $from, string $to): array
    {
        $start = strtotime(($from !== '' ? $from : date('Y-m-01', strtotime('-11 months'))));
        $end = strtotime($to !== '' ? $to : 'now');
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $months = [];
        for ($t = strtotime(date('Y-m-01', $start)); $t <= $end && count($months) < 36; $t = strtotime('+1 month', $t)) {
            $months[date('Y-m', $t)] = 0;
        }
        foreach (self::pairs($sql) as $ym => $n) {
            if (isset($months[$ym])) {
                $months[$ym] = $n;
            }
        }
        $out = [];
        foreach ($months as $ym => $n) {
            $out[date('M y', strtotime($ym . '-01'))] = $n;
        }
        return $out;
    }

    private static function range(string $column, string $from, string $to): string
    {
        $sql = '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= " AND $column >= '$from'";
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= " AND $column < '" . date('Y-m-d', strtotime($to . ' +1 day')) . "'";
        }
        return $sql;
    }

    private static function pairs(string $sql): array
    {
        $out = [];
        foreach (Database::connection()->query($sql)->fetchAll(\PDO::FETCH_NUM) as [$label, $n]) {
            $out[(string) $label] = (float) $n;
        }
        return $out;
    }

    private static function value(string $sql)
    {
        return Database::connection()->query($sql)->fetchColumn();
    }
}
