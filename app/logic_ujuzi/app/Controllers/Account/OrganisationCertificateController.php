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
        $filters = ['organisation_id' => $orgId, 'course_id' => $onlyCourseId];
        if (($this->user['role_slug'] ?? '') === 'course_branch_admin') {
            $branchIds = array_map(static fn(array $b): int => (int) $b['id'], OrganisationBranch::forBranchAdmin((int) $this->user['id']));
            $filters['user_ids'] = [];
            if ($branchIds) {
                $stmt = Database::connection()->prepare(
                    "SELECT DISTINCT user_id FROM organisation_memberships WHERE status = 'approved' AND organisation_id = ? AND branch_id IN ("
                    . implode(',', array_fill(0, count($branchIds), '?')) . ')'
                );
                $stmt->execute(array_merge([$orgId], $branchIds));
                $filters['user_ids'] = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
            }
        }
        $out = \App\Services\CertificateRegistry::earned($filters);
        usort($out, static fn(array $a, array $b): int => [$a['course_title'], $a['name']] <=> [$b['course_title'], $b['name']]);
        return $out;
    }

    /** Many of this organisation's certificates on one page, one per sheet, to save as one PDF. */
    public function download(): void
    {
        $orgId = $this->organisationId();
        $allowed = [];
        foreach ($this->earnedCertificates($orgId) as $row) {
            $allowed[$row['user_id'] . '-' . $row['course_id']] = true;
        }
        $payloads = [];
        foreach (array_slice((array) Request::query('pairs', []), 0, 300) as $pair) {
            if (!isset($allowed[(string) $pair])) {
                continue; // not one of this organisation's (or this branch's) certificates
            }
            [$userId, $courseId] = array_map('intval', explode('-', (string) $pair));
            if ($payload = \App\Services\CertificateRegistry::earnedPayload($userId, $courseId)) {
                $payloads[] = $payload;
            }
        }
        \App\Core\View::render('partials.certificate-batch', [
            'payloads' => $payloads,
            'batchTitle' => 'Certificates (' . count($payloads) . ')',
            'backUrl' => '/account/certificates',
        ]);
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
