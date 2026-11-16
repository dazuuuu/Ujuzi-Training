<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Database;
use App\Core\Request;
use App\Services\PdfTable;
use App\Services\SpreadsheetExport;

/** Who passed and failed an exam-only course (NTSA, NITA…): for the tutor, or the whole organisation's admins. */
class ExamResultsController extends BaseAccountController
{
    public function index(): void
    {
        $courseId = (int) Request::query('course', 0);
        $courses = $this->examCourses();
        $rows = $courseId ? $this->results($courseId, $courses) : [];
        if ((string) Request::query('export', '') !== '' && $courseId) {
            $this->export($courses[$courseId]['title'] ?? 'exam', $rows, (string) Request::query('export'));
        }
        $this->render('account.exam-results.index', [
            'pageTitle' => 'Exam results',
            'activeNav' => 'exam_results',
            'courses' => $courses,
            'courseId' => $courseId,
            'rows' => $rows,
        ]);
    }

    /** @return array<int, array> exam-only courses this person may report on, keyed by id */
    private function examCourses(): array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        try {
            if ($role === 'trainer') {
                $stmt = Database::connection()->prepare('SELECT id, title, final_pass_percent FROM courses WHERE exam_only = 1 AND trainer_user_id = ? ORDER BY title');
                $stmt->execute([(int) $this->user['id']]);
            } elseif (Authz::isOrganisationAdmin($this->user) || $role === 'course_branch_admin') {
                $org = \App\Models\Announcement::organisationOf($this->user);
                $stmt = Database::connection()->prepare('SELECT id, title, final_pass_percent FROM courses WHERE exam_only = 1 AND organisation_id = ? ORDER BY title');
                $stmt->execute([$org]);
            } else {
                flashError('Exam results are for tutors and organisation admins.');
                redirect('/account/dashboard');
            }
            $out = [];
            foreach ($stmt->fetchAll() as $c) {
                $out[(int) $c['id']] = $c;
            }
            return $out;
        } catch (\PDOException $e) {
            return []; // before the exams-only update
        }
    }

    private function results(int $courseId, array $courses): array
    {
        if (!isset($courses[$courseId])) {
            return [];
        }
        $stmt = Database::connection()->prepare(
            "SELECT u.registration_number, u.first_name, u.last_name, u.phone, u.email, f.score, f.passed, f.completed_at, f.updated_at
             FROM course_final_exam_progress f INNER JOIN users u ON u.id = f.user_id
             WHERE f.course_id = ? ORDER BY f.passed DESC, f.score DESC, u.first_name"
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    private function export(string $title, array $rows, string $format): void
    {
        $headers = ['Reg. No.', 'Name', 'Phone', 'Email', 'Score %', 'Result', 'Date'];
        $data = array_map(static fn(array $r): array => [
            $r['registration_number'], trim($r['first_name'] . ' ' . $r['last_name']), $r['phone'], $r['email'], (int) $r['score'],
            $r['passed'] ? 'PASSED' : 'FAILED', date('Y-m-d', strtotime((string) ($r['completed_at'] ?: $r['updated_at']))),
        ], $rows);
        $name = 'exam-results-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        if ($format === 'pdf') {
            PdfTable::download($name, 'Exam results — ' . $title, appName() . ' · ' . count($data) . ' candidates · generated ' . date('j M Y H:i'), $headers, $data, null, true);
        }
        SpreadsheetExport::download($name, 'Results', $headers, $data);
    }
}
