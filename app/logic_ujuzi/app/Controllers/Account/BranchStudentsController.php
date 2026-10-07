<?php

namespace App\Controllers\Account;

use App\Core\Database;
use App\Core\Request;
use App\Models\OrganisationBranch;
use App\Services\AttachmentReport;
use App\Services\BranchScope;
use App\Services\PdfTable;
use App\Services\SpreadsheetExport;
use App\Services\StudentRoster;
use App\Services\WalletService;

/**
 * The course branch admin's students: who is in their branch, what they
 * enrolled for (filterable by date), and adding students — one at a time or
 * from the upload template. Lists download as Excel or open as PDF to view
 * and print.
 */
class BranchStudentsController extends BaseAccountController
{
    public function index(): void
    {
        [$orgId, $branches] = $this->scope();
        [$from, $to] = AttachmentReport::dateRange();
        $tab = (string) Request::query('tab', 'students');
        $this->render('account.branch-students.index', [
            'pageTitle' => 'Students',
            'activeNav' => 'branch_students',
            'tab' => in_array($tab, ['students', 'tutors', 'enrolments', 'add'], true) ? $tab : 'students',
            'branches' => $branches,
            'students' => $this->students($orgId, $branches),
            'tutors' => $this->students($orgId, $branches, 'trainer'),
            'enrolments' => $this->enrolments($orgId, $branches, $from, $to),
            'from' => $from,
            'to' => $to,
            'problems' => $_SESSION['roster_problems'] ?? [],
        ]);
        unset($_SESSION['roster_problems']);
    }

    public function store(): void
    {
        [$orgId, $branches] = $this->scope();
        $this->csrf();
        $branchId = $this->postedBranch($branches);
        $error = StudentRoster::add([
            'first_name' => Request::post('first_name', ''),
            'other_names' => Request::post('other_names', ''),
            'last_name' => Request::post('last_name', ''),
            'email' => Request::post('email', ''),
            'phone' => Request::post('phone', ''),
        ], $orgId, $branchId, (int) $this->user['id'], $role = (string) Request::post('role', 'student'));
        $error === null
            ? flashSuccess(($role === 'trainer' ? 'Tutor' : 'Student') . ' added. They can sign in with the default password and will be asked to change it.')
            : flashError($error);
        redirect('/account/branch-students?tab=add');
    }

    public function import(): void
    {
        [$orgId, $branches] = $this->scope();
        $this->csrf();
        $file = Request::file('csv_file');
        if (!$file || empty($file['tmp_name'])) {
            flashError('Choose the filled-in template (CSV) to upload.');
            redirect('/account/branch-students?tab=add');
        }
        [$created, $problems] = StudentRoster::import($file['tmp_name'], $orgId, $this->postedBranch($branches), (int) $this->user['id'], (string) Request::post('role', 'student'));
        $_SESSION['roster_problems'] = array_slice($problems, 0, 50);
        $created
            ? flashSuccess('Added ' . $created . ' ' . (Request::post('role') === 'trainer' ? 'tutor' : 'student') . ($created === 1 ? '' : 's') . ($problems ? '. ' . count($problems) . ' row(s) were skipped — see below.' : '.'))
            : flashError('No students were added. ' . ($problems ? 'See the rows below.' : ''));
        redirect('/account/branch-students?tab=add');
    }

    public function template(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="students-upload-template.csv"');
        echo "\xEF\xBB\xBF" . StudentRoster::templateCsv();
        exit;
    }

    /** Students or enrolments as Excel, or as a PDF opened in the browser to view / print. */
    public function export(string $list, string $format): void
    {
        [$orgId, $branches] = $this->scope();
        [$from, $to] = AttachmentReport::dateRange();
        if ($list === 'enrolments') {
            $headers = ['Enrolled', 'Student', 'Reg. No.', 'Phone', 'Course', 'Fee (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)'];
            $rows = array_map(static fn(array $r): array => [
                $r['enrolled_at'] ? date('Y-m-d', strtotime($r['enrolled_at'])) : '', $r['name'], $r['registration_number'], $r['phone'],
                $r['course_title'], (float) $r['fee'], (float) $r['paid'], (float) $r['balance'],
            ], $this->enrolments($orgId, $branches, $from, $to));
            $title = 'Enrolments';
        } else {
            $headers = ['Reg. No.', 'Name', 'Email', 'Phone', 'Branch', 'Courses', 'Joined'];
            $rows = array_map(static fn(array $r): array => [
                $r['registration_number'], $r['name'], $r['email'], $r['phone'], $r['branch_title'], (int) $r['courses'],
                $r['joined_at'] ? date('Y-m-d', strtotime($r['joined_at'])) : '',
            ], $this->students($orgId, $branches));
            $title = 'Students';
        }
        $name = strtolower($title) . '-' . date('Y-m-d');
        if ($format === 'pdf') {
            $range = $list === 'enrolments' && ($from !== '' || $to !== '') ? ' · ' . ($from ?: 'start') . ' to ' . ($to ?: date('Y-m-d')) : '';
            PdfTable::download($name, $title . ' — ' . implode(', ', array_column($branches, 'title')), appName() . ' · ' . count($rows) . ' records' . $range . ' · generated ' . date('j M Y H:i'), $headers, $rows, null, true);
        }
        SpreadsheetExport::download($name, $title, $headers, $rows);
    }

    /** Students approved into this admin's branches. */
    private function students(int $orgId, array $branches, string $roleSlug = 'student'): array
    {
        $ids = array_map(static fn(array $b): int => (int) $b['id'], $branches);
        $stmt = Database::connection()->prepare(
            "SELECT u.id, u.first_name, u.other_names, u.last_name, u.email, u.phone, u.registration_number,
                    b.title AS branch_title, m.reviewed_at AS joined_at,
                    (SELECT COUNT(*) FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id
                      WHERE e.user_id = u.id AND c.organisation_id = m.organisation_id) AS courses
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = ?
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.status = 'approved' AND m.organisation_id = ? AND m.branch_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')
             ORDER BY u.first_name, u.last_name'
        );
        $stmt->execute(array_merge([$roleSlug, $orgId], $ids));
        return array_map(static function (array $r): array {
            $r['name'] = userFullName($r);
            $r['email'] = (string) ($r['email'] ?? '');
            $r['phone'] = (string) ($r['phone'] ?? '');
            $r['registration_number'] = (string) ($r['registration_number'] ?? '');
            return $r;
        }, $stmt->fetchAll());
    }

    /** Enrolments of those students in this organisation's courses, with each course's own fee standing. */
    private function enrolments(int $orgId, array $branches, string $from, string $to): array
    {
        $students = $this->students($orgId, $branches);
        if (!$students) {
            return [];
        }
        $byId = array_column($students, null, 'id');
        $fees = WalletService::feeSummaries(array_keys($byId));
        $stmt = Database::connection()->prepare(
            'SELECT e.user_id, e.course_id, e.enrolled_at, c.title FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id AND c.organisation_id = ?
             WHERE e.user_id IN (' . implode(',', array_map('intval', array_keys($byId))) . ')'
             . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? " AND e.enrolled_at >= '$from'" : '')
             . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? " AND e.enrolled_at < '" . date('Y-m-d', strtotime($to . ' +1 day')) . "'" : '')
             . ' ORDER BY e.enrolled_at DESC'
        );
        $stmt->execute([$orgId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $student = $byId[(int) $row['user_id']];
            $item = null;
            foreach ($fees[(int) $row['user_id']]['items'] ?? [] as $candidate) {
                if ((int) $candidate['course_id'] === (int) $row['course_id']) {
                    $item = $candidate;
                }
            }
            $out[] = [
                'enrolled_at' => $row['enrolled_at'],
                'name' => $student['name'],
                'registration_number' => $student['registration_number'],
                'phone' => $student['phone'],
                'course_title' => $row['title'],
                'fee' => $item['fee'] ?? 0,
                'paid' => $item['paid'] ?? 0,
                'balance' => $item['balance'] ?? 0,
                'progress' => $item['progress'] ?? 0,
            ];
        }
        return $out;
    }

    /** [organisation id, branches] for this course branch admin. */
    private function scope(): array
    {
        if (($this->user['role_slug'] ?? '') !== 'course_branch_admin') {
            flashError('This page is for branch admins of organisations providing courses.');
            redirect('/account/dashboard');
        }
        [$orgId] = BranchScope::courseBranch((int) $this->user['id']);
        $branches = array_values(array_filter(OrganisationBranch::forBranchAdmin((int) $this->user['id']), static fn(array $b): bool => (int) ($b['organisation_id'] ?? 0) === $orgId));
        if (!$orgId || !$branches) {
            flashError('No branch has been assigned to you yet.');
            redirect('/account/dashboard');
        }
        return [$orgId, $branches];
    }

    private function postedBranch(array $branches): int
    {
        $id = (int) Request::post('branch_id', 0);
        foreach ($branches as $branch) {
            if ((int) $branch['id'] === $id) {
                return $id;
            }
        }
        return (int) $branches[0]['id'];
    }

    private function csrf(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/branch-students');
        }
    }
}
