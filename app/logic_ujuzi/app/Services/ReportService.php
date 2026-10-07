<?php

namespace App\Services;

use App\Core\Database;
use App\Models\AttachmentApplication;

/**
 * Super Admin's reports: one table per kind of data, the same rows for the
 * page, the Excel file and the PDF. Dates filter what each section is about
 * (when a student registered, enrolled, deposited, or asked for attachment).
 */
class ReportService
{
    /** key => [title, what it lists] */
    public const SECTIONS = [
        'students' => ['Students', 'Every registered student with their registration number, organisation, courses and fees.'],
        'course-organisations' => ['Organisations providing courses', 'Contacts, branches, categories, courses, tutors and students of each.'],
        'attachment-organisations' => ['Organisations providing attachment', 'Contacts, branches, where they are approved, and their attachees.'],
        'enrolments' => ['Enrolments', 'Every student enrolled in every course, with progress and what they have paid.'],
        'finances' => ['Finances', 'Fees due, collected and outstanding for every course.'],
        'deposits' => ['Deposits', 'Money students put into their wallets.'],
        'attachments-accepted' => ['Attachments — accepted', 'Students currently on attachment, with what they have paid and their courses.'],
        'attachments-completed' => ['Attachments — completed', 'Students who finished attachment, with what they paid and their courses.'],
    ];

    /** @return array{title: string, headers: string[], rows: array<int, array>, totals: ?array} */
    public static function build(string $section, string $from = '', string $to = ''): array
    {
        [$title] = self::SECTIONS[$section];
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $section))));
        return ['title' => $title] + self::$method($from, $to);
    }

    private static function dateWhere(string $column, string $from, string $to, array &$params): string
    {
        $sql = '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= " AND $column >= ?";
            $params[] = $from;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= " AND $column < ?";
            $params[] = date('Y-m-d', strtotime($to . ' +1 day'));
        }
        return $sql;
    }

    private static function rows(string $sql, array $params = []): array
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function date(?string $at): string
    {
        return $at ? date('Y-m-d', strtotime($at)) : '';
    }

    private static function money(float $n): float
    {
        return round($n, 2);
    }

    private static function students(string $from, string $to): array
    {
        $params = [];
        $students = self::rows(
            "SELECT u.id, u.registration_number, u.first_name, u.last_name, u.email, u.phone, u.account_status, u.created_at,
                    (SELECT GROUP_CONCAT(DISTINCT o.name SEPARATOR ', ') FROM organisation_memberships m
                       INNER JOIN organisations o ON o.id = m.organisation_id WHERE m.user_id = u.id AND m.status = 'approved') AS organisations
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'student'" . self::dateWhere('u.created_at', $from, $to, $params) . '
             ORDER BY u.registration_seq IS NULL, u.registration_seq, u.id',
            $params
        );
        $fees = WalletService::feeSummaries(array_map('intval', array_column($students, 'id')));
        $rows = [];
        foreach ($students as $s) {
            $f = $fees[(int) $s['id']] ?? null;
            $rows[] = [
                (string) $s['registration_number'], trim($s['first_name'] . ' ' . $s['last_name']), (string) $s['email'], (string) $s['phone'],
                (string) ($s['organisations'] ?? ''), implode(', ', array_column($f['items'] ?? [], 'title')),
                self::money($f['fee'] ?? 0), self::money($f['paid'] ?? 0), self::money($f['balance'] ?? 0),
                self::money(WalletService::balanceKsh((int) $s['id'])), ucfirst((string) $s['account_status']), self::date($s['created_at']),
            ];
        }
        return [
            'headers' => ['Registration no.', 'Name', 'Email', 'Phone', 'Organisation', 'Courses enrolled', 'Fees (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)', 'Wallet (Ksh)', 'Status', 'Registered'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' students', '', '', '', '', array_sum(array_column($rows, 6)), array_sum(array_column($rows, 7)), array_sum(array_column($rows, 8)), array_sum(array_column($rows, 9)), '', ''],
        ];
    }

    private static function courseOrganisations(string $from, string $to): array
    {
        $params = [];
        $orgs = self::rows(
            "SELECT o.*,
                    (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name, ' <', u.email, '>') SEPARATOR ', ') FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.organisation_id = o.id AND r.slug = 'organisation_admin') AS admins,
                    (SELECT COUNT(*) FROM organisation_branches b WHERE b.organisation_id = o.id AND b.owner_type = 'organisation') AS branches,
                    (SELECT COUNT(*) FROM organisation_categories c WHERE c.organisation_id = o.id) AS categories,
                    (SELECT COUNT(*) FROM courses c WHERE c.organisation_id = o.id) AS courses,
                    (SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.organisation_id = o.id AND r.slug = 'trainer') AS tutors,
                    (SELECT COUNT(*) FROM organisation_memberships m INNER JOIN users u ON u.id = m.user_id INNER JOIN roles r ON r.id = u.role_id WHERE m.organisation_id = o.id AND m.status = 'approved' AND r.slug = 'student') AS students
             FROM organisations o
             WHERE EXISTS (SELECT 1 FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.organisation_id = o.id AND r.slug = 'organisation_admin')"
             . self::dateWhere('o.created_at', $from, $to, $params) . ' ORDER BY o.name',
            $params
        );
        $rows = array_map(static fn(array $o): array => [
            (string) $o['name'], (string) ($o['admins'] ?? ''), (string) ($o['email'] ?? ''), (string) ($o['phone'] ?? ''), (string) ($o['location'] ?? ''),
            (int) $o['branches'], (int) $o['categories'], (int) $o['courses'], (int) $o['tutors'], (int) $o['students'],
            !empty($o['is_active']) ? 'Active' : 'Inactive', self::date($o['created_at']),
        ], $orgs);
        return [
            'headers' => ['Organisation', 'Admins', 'Email', 'Phone', 'Location', 'Branches', 'Categories', 'Courses', 'Tutors', 'Students', 'Status', 'Joined'],
            'rows' => $rows,
            'totals' => null,
        ];
    }

    private static function attachmentOrganisations(string $from, string $to): array
    {
        $params = [];
        $providers = self::rows(
            "SELECT u.id, u.first_name, u.last_name, u.email AS user_email, u.phone AS user_phone, u.created_at,
                    o.name AS org_name, o.email, o.phone, o.location,
                    (SELECT COUNT(*) FROM organisation_branches b WHERE b.owner_type = 'attachment_provider' AND b.owner_user_id = u.id) AS branches,
                    (SELECT GROUP_CONCAT(DISTINCT co.name SEPARATOR ', ') FROM attachment_provider_audiences a INNER JOIN organisations co ON co.id = a.organisation_id WHERE a.provider_user_id = u.id AND a.status = 'approved') AS approved_by,
                    (SELECT COUNT(*) FROM attachment_provider_audiences a WHERE a.provider_user_id = u.id AND a.status = 'pending') AS waiting,
                    (SELECT COUNT(*) FROM attachment_applications a WHERE a.provider_user_id = u.id AND a.status = 'accepted') AS accepted,
                    (SELECT COUNT(*) FROM attachment_applications a WHERE a.provider_user_id = u.id AND a.status IN ('completed', 'recommended')) AS completed
             FROM users u INNER JOIN roles r ON r.id = u.role_id LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE r.slug = 'attachment_trainer'" . self::dateWhere('u.created_at', $from, $to, $params) . ' ORDER BY o.name, u.email',
            $params
        );
        $rows = array_map(static fn(array $p): array => [
            (string) ($p['org_name'] ?: trim($p['first_name'] . ' ' . $p['last_name'])), trim($p['first_name'] . ' ' . $p['last_name']),
            (string) ($p['email'] ?: $p['user_email']), (string) ($p['phone'] ?: $p['user_phone']), (string) ($p['location'] ?? ''),
            (int) $p['branches'], (string) ($p['approved_by'] ?? ''), (int) $p['waiting'], (int) $p['accepted'], (int) $p['completed'], self::date($p['created_at']),
        ], $providers);
        return [
            'headers' => ['Organisation', 'Contact person', 'Email', 'Phone', 'Location', 'Branches', 'Approved by', 'Requests waiting', 'Attachees now', 'Completed', 'Joined'],
            'rows' => $rows,
            'totals' => null,
        ];
    }

    private static function enrolments(string $from, string $to): array
    {
        $params = [];
        $enrolments = self::rows(
            "SELECT e.user_id, e.course_id, e.enrolled_at, e.created_at, u.registration_number, u.first_name, u.last_name,
                    c.title, c.enrollment_fee_ksh, o.name AS org_name, cat.name AS category_name
             FROM course_enrollments e
             INNER JOIN users u ON u.id = e.user_id INNER JOIN courses c ON c.id = e.course_id
             INNER JOIN organisations o ON o.id = c.organisation_id LEFT JOIN organisation_categories cat ON cat.id = c.category_id
             WHERE 1 = 1" . self::dateWhere('COALESCE(e.enrolled_at, e.created_at)', $from, $to, $params) . '
             ORDER BY COALESCE(e.enrolled_at, e.created_at) DESC',
            $params
        );
        $fees = WalletService::feeSummaries(array_values(array_unique(array_map('intval', array_column($enrolments, 'user_id')))));
        $rows = [];
        foreach ($enrolments as $e) {
            $item = null;
            foreach (($fees[(int) $e['user_id']]['items'] ?? []) as $candidate) {
                if ((int) $candidate['course_id'] === (int) $e['course_id']) { $item = $candidate; break; }
            }
            $rows[] = [
                (string) $e['registration_number'], trim($e['first_name'] . ' ' . $e['last_name']), (string) $e['title'], (string) $e['org_name'],
                (string) ($e['category_name'] ?? ''), self::date($e['enrolled_at'] ?: $e['created_at']), self::money((float) $e['enrollment_fee_ksh']),
                self::money($item['paid'] ?? 0), self::money($item['balance'] ?? 0), (int) ($item['progress'] ?? 0),
                !empty($item['final_passed']) ? 'Completed' : 'In progress',
            ];
        }
        return [
            'headers' => ['Registration no.', 'Student', 'Course', 'Organisation', 'Category', 'Enrolled', 'Fee (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)', 'Progress %', 'Status'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' enrolments', '', '', '', '', array_sum(array_column($rows, 6)), array_sum(array_column($rows, 7)), array_sum(array_column($rows, 8)), '', ''],
        ];
    }

    private static function finances(string $from, string $to): array
    {
        $params = [];
        $payWhere = self::dateWhere('t.created_at', $from, $to, $params);
        $courses = self::rows(
            "SELECT c.id, c.title, c.enrollment_fee_ksh, o.name AS org_name, CONCAT(u.first_name, ' ', u.last_name) AS tutor,
                    (SELECT COUNT(*) FROM course_enrollments e WHERE e.course_id = c.id) AS students,
                    (SELECT COALESCE(SUM(t.amount_ksh), 0) FROM wallet_transactions t WHERE t.course_id = c.id AND t.type = 'course_payment'$payWhere) AS collected
             FROM courses c INNER JOIN organisations o ON o.id = c.organisation_id INNER JOIN users u ON u.id = c.trainer_user_id
             ORDER BY o.name, c.title",
            $params
        );
        $rows = [];
        foreach ($courses as $c) {
            $due = (float) $c['enrollment_fee_ksh'] * (int) $c['students'];
            $rows[] = [
                (string) $c['org_name'], (string) $c['title'], trim((string) $c['tutor']), (int) $c['students'], self::money((float) $c['enrollment_fee_ksh']),
                self::money($due), self::money((float) $c['collected']), self::money(max(0, $due - (float) $c['collected'])),
            ];
        }
        return [
            'headers' => ['Organisation', 'Course', 'Tutor', 'Students', 'Fee each (Ksh)', 'Fees due (Ksh)', 'Collected (Ksh)', 'Outstanding (Ksh)'],
            'rows' => $rows,
            'totals' => ['Total', '', '', array_sum(array_column($rows, 3)), '', array_sum(array_column($rows, 5)), array_sum(array_column($rows, 6)), array_sum(array_column($rows, 7))],
        ];
    }

    private static function deposits(string $from, string $to): array
    {
        $params = [];
        $deposits = self::rows(
            "SELECT t.*, u.registration_number, u.first_name, u.last_name, u.phone
             FROM wallet_transactions t INNER JOIN users u ON u.id = t.user_id
             WHERE t.type = 'deposit'" . self::dateWhere('t.created_at', $from, $to, $params) . ' ORDER BY t.created_at DESC',
            $params
        );
        $rows = array_map(static fn(array $t): array => [
            self::date($t['created_at']), (string) $t['registration_number'], trim($t['first_name'] . ' ' . $t['last_name']), (string) $t['phone'],
            self::money((float) $t['amount_ksh']), WalletService::coins((float) $t['amount_ksh']), (string) ($t['provider'] ?? ''), (string) ($t['reference'] ?? ''),
        ], $deposits);
        return [
            'headers' => ['Date', 'Registration no.', 'Student', 'Phone', 'Amount (Ksh)', 'Coins', 'Paid via', 'Reference'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' deposits', '', '', array_sum(array_column($rows, 4)), array_sum(array_column($rows, 5)), '', ''],
        ];
    }

    private static function attachmentsAccepted(string $from, string $to): array
    {
        return self::attachments(AttachmentApplication::STATUS_ACCEPTED, $from, $to);
    }

    private static function attachmentsCompleted(string $from, string $to): array
    {
        return self::attachments(AttachmentApplication::STATUS_COMPLETED, $from, $to);
    }

    private static function attachments(string $status, string $from, string $to): array
    {
        $applications = AttachmentApplication::report(['status' => $status, 'from' => $from, 'to' => $to]);
        $fees = WalletService::feeSummaries(array_values(array_unique(array_map('intval', array_column($applications, 'student_user_id')))));
        $rows = [];
        foreach ($applications as $a) {
            $f = $fees[(int) $a['student_user_id']] ?? null;
            $rows[] = [
                (string) ($a['registration_number'] ?? ''), trim($a['first_name'] . ' ' . $a['last_name']), (string) $a['email'], (string) ($a['phone'] ?? ''),
                implode(', ', array_column($f['items'] ?? [], 'title')), (string) ($a['category_name'] ?? ''),
                (string) ($a['provider_organisation_name'] ?: trim($a['provider_first_name'] . ' ' . $a['provider_last_name'])), (string) ($a['branch_title'] ?? ''),
                self::date($a['accepted_at'] ?? null), self::date($a['recommended_at'] ?? ($a['completed_at'] ?? null)),
                self::money($f['fee'] ?? 0), self::money($f['paid'] ?? 0), self::money($f['balance'] ?? 0),
            ];
        }
        return [
            'headers' => ['Registration no.', 'Student', 'Email', 'Phone', 'Courses done', 'Category', 'Attached at', 'Branch', 'Accepted', 'Completed', 'Fees (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)'],
            'rows' => $rows,
            'totals' => ['Total', count($rows) . ' students', '', '', '', '', '', '', '', '', array_sum(array_column($rows, 10)), array_sum(array_column($rows, 11)), array_sum(array_column($rows, 12))],
        ];
    }
}
