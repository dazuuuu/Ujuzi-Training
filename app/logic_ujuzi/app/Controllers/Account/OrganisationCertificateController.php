<?php

namespace App\Controllers\Account;

use App\Core\Database;
use App\Core\Request;
use App\Models\Course;
use App\Models\OrganisationBranch;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\DocumentLayout;
use App\Services\UploadException;

/**
 * Certificates for an organisation providing courses, used by its admin and
 * its course branch admins: upload the organisation's own certificate design
 * and place the details on it, then open (and print) the certificate of any
 * of their students who earned one. Certificates are generated on this
 * organisation's design; with none uploaded, the platform-wide design.
 */
class OrganisationCertificateController extends BaseAccountController
{
    public function index(): void
    {
        $orgId = $this->organisationId();
        $template = CertificateService::ownTemplatePath($orgId);
        $this->render('account.certificates.index', [
            'pageTitle' => 'Certificates',
            'activeNav' => 'certificates',
            'template' => $template,
            'isPdf' => $template ? CertificateService::isPdf($template) : false,
            'layout' => DocumentLayout::get('certificate', $orgId),
            'usesPlatformDesign' => !$template && CertificateService::templatePath(null) !== null,
            'mode' => CertificateService::mode($orgId),
            'globalTemplate' => CertificateService::ownTemplatePath(null),
            'earned' => $this->earnedCertificates($orgId),
        ]);
    }

    public function upload(): void
    {
        $orgId = $this->organisationId();
        $this->csrf();
        if (Request::post('remove_template')) {
            CertificateService::setTemplate($orgId, null);
            flashSuccess('Design removed. Your certificates now use the platform design.');
            redirect('/account/certificates');
        }
        $file = Request::file('template');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a PDF or image of your certificate design.');
            redirect('/account/certificates');
        }
        try {
            CertificateService::setTemplate($orgId, CertificateService::storeUpload($file));
            flashSuccess('Certificate design saved. Now drag each detail to where it goes on your design.');
        } catch (UploadException $e) {
            flashError($e->getMessage());
        }
        redirect('/account/certificates#layout-certificate');
    }

    /** Choose the global Ujuzi Training certificate or the organisation's own. */
    public function mode(): void
    {
        $orgId = $this->organisationId();
        $this->csrf();
        $mode = (string) Request::post('mode', CertificateService::MODE_GLOBAL);
        CertificateService::setMode($orgId, $mode);
        flashSuccess($mode === CertificateService::MODE_ORGANISATION
            ? (CertificateService::ownTemplatePath($orgId) ? 'Your students now get your organisational certificate.' : 'Organisational certificate chosen. Upload your design below — until then your students get the global certificate.')
            : 'Your students now get the global Ujuzi Training certificate.');
        redirect('/account/certificates');
    }

    public function layout(): void
    {
        $orgId = $this->organisationId();
        $this->csrf();
        if (Request::post('reset') === '1') {
            DocumentLayout::reset('certificate', $orgId);
            flashSuccess('Positions reset to the defaults.');
        } else {
            $layout = json_decode((string) Request::post('layout', '{}'), true);
            DocumentLayout::save('certificate', is_array($layout) ? $layout : [], $orgId);
            flashSuccess('Positions saved. Your students\' certificates place the details exactly there.');
        }
        redirect('/account/certificates#layout-certificate');
    }

    /** One student's certificate for one of this organisation's courses. */
    public function show(string $userId, string $courseId): void
    {
        $orgId = $this->organisationId();
        $course = Course::find((int) $courseId);
        $student = User::find((int) $userId);
        $allowed = $course && $student && (int) $course['organisation_id'] === $orgId
            && in_array((int) $student['id'], array_column($this->earnedCertificates($orgId, (int) $course['id']), 'user_id'), true);
        if (!$allowed) {
            flashError('That certificate is not available. The student must finish the course (and its attachment, if it needs one) first.');
            redirect('/account/certificates');
        }
        $this->render('account.certificate.show', [
            'pageTitle' => $course['title'] . ' — ' . userFullName($student),
            'activeNav' => 'certificates',
            'payload' => CertificateService::payload($student, [$course]),
            'completedCourses' => [],
            'singleCourse' => $course,
            'issuedTo' => $student,
        ]);
    }

    /**
     * Certificates students earned on this organisation's courses, newest
     * first: [user_id, course_id, name, registration_number, course_title].
     * A course branch admin only sees students approved into their branches.
     */
    private function earnedCertificates(int $orgId, ?int $onlyCourseId = null): array
    {
        $params = [$orgId, $orgId];
        $courseFilter = '';
        if ($onlyCourseId) {
            $courseFilter = ' AND c.id = ?';
            $params[] = $onlyCourseId;
        }
        $rows = Database::connection()->prepare(
            "SELECT DISTINCT x.user_id, c.id AS course_id FROM (
                SELECT e.user_id, e.course_id FROM course_enrollments e
                INNER JOIN courses c2 ON c2.id = e.course_id AND c2.organisation_id = ?
                UNION
                SELECT cc.user_id, cc.course_id FROM course_completions cc
                INNER JOIN courses c3 ON c3.id = cc.course_id AND c3.organisation_id = ?
             ) x
             INNER JOIN courses c ON c.id = x.course_id
             WHERE c.certificate_enabled = 1" . $courseFilter
        );
        try {
            $rows->execute($params);
            $pairs = $rows->fetchAll();
        } catch (\Throwable $e) {
            return []; // before the completions migration
        }

        $branchStudents = null;
        if (($this->user['role_slug'] ?? '') === 'course_branch_admin') {
            $branchIds = array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin((int) $this->user['id']));
            $branchStudents = [];
            if ($branchIds) {
                $stmt = Database::connection()->prepare(
                    "SELECT DISTINCT user_id FROM organisation_memberships WHERE status = 'approved' AND organisation_id = ? AND branch_id IN ("
                    . implode(',', array_fill(0, count($branchIds), '?')) . ')'
                );
                $stmt->execute(array_merge([$orgId], $branchIds));
                $branchStudents = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
            }
        }

        $courses = [];
        $out = [];
        foreach ($pairs as $pair) {
            $userId = (int) $pair['user_id'];
            $courseId = (int) $pair['course_id'];
            if ($branchStudents !== null && !in_array($userId, $branchStudents, true)) {
                continue;
            }
            $courses[$courseId] ??= Course::find($courseId);
            if (!$courses[$courseId] || !Course::isCertifiableFor($userId, $courses[$courseId])) {
                continue;
            }
            $student = User::find($userId);
            if (!$student) {
                continue;
            }
            $out[] = [
                'user_id' => $userId,
                'course_id' => $courseId,
                'name' => userFullName($student),
                'registration_number' => (string) ($student['registration_number'] ?? ''),
                'phone' => (string) ($student['phone'] ?? ''),
                'course_title' => (string) $courses[$courseId]['title'],
            ];
        }
        usort($out, static fn(array $a, array $b): int => [$a['course_title'], $a['name']] <=> [$b['course_title'], $b['name']]);
        return $out;
    }

    /** The organisation this person issues certificates for: their own, or their branch's. */
    private function organisationId(): int
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        if ($role === 'organisation_admin' && !empty($this->user['organisation_id'])) {
            return (int) $this->user['organisation_id'];
        }
        if ($role === 'course_branch_admin') {
            foreach (OrganisationBranch::forBranchAdmin((int) $this->user['id']) as $branch) {
                if (!empty($branch['organisation_id'])) {
                    return (int) $branch['organisation_id'];
                }
            }
        }
        flashError('Certificates are managed by organisations providing courses and their branch admins.');
        redirect('/account/dashboard');
    }

    private function csrf(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/certificates');
        }
    }
}
