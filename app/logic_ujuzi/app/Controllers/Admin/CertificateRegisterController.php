<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Services\CertificateRegistry;
use App\Services\SpreadsheetExport;

/**
 * Super Admin's certificates: every certificate in the system (earned by
 * students, and generated here for anyone), filtered, downloaded one by one
 * or all at once; and the generator that makes certificates for any list of
 * names in one go.
 */
class CertificateRegisterController extends BaseAdminController
{
    /** The most certificates one download page draws. */
    private const BATCH_LIMIT = 300;

    public function index(): void
    {
        $filters = $this->filters();
        $tab = Request::query('tab') === 'generated' ? 'generated' : 'earned';
        $earned = $tab === 'earned' ? CertificateRegistry::earned($filters) : [];
        $generated = $tab === 'generated' ? CertificateRegistry::issued($filters) : [];

        if (Request::query('export') === 'xlsx') {
            $tab === 'earned'
                ? SpreadsheetExport::download('certificates-' . date('Y-m-d'), 'Certificates', ['#', 'Certificate No.', 'Name', 'Reg. No.', 'Phone', 'Email', 'Course', 'Organisation', 'Completed'],
                    array_map(static fn(array $r, int $i): array => [$i + 1, $r['certificate_no'], $r['name'], $r['registration_number'], $r['phone'], $r['email'], $r['course_title'], $r['organisation_name'], $r['completed_at']], $earned, array_keys($earned)))
                : SpreadsheetExport::download('generated-certificates-' . date('Y-m-d'), 'Generated', ['#', 'Certificate No.', 'Name', 'ID / Reg. No.', 'Skills', 'Organisation', 'Issued'],
                    array_map(static fn(array $r, int $i): array => [$i + 1, $r['certificate_no'], $r['recipient_name'], (string) $r['id_number'], $r['skills'], (string) $r['organisation_name'], $r['issued_on']], $generated, array_keys($generated)));
        }

        View::render('admin.certificates.index', [
            'pageTitle' => 'Certificates',
            'activeNav' => 'certificates',
            'certTab' => $tab,
            'filters' => $filters,
            'earned' => $earned,
            'generated' => $generated,
            'organisations' => $this->organisationOptions(),
            'courses' => $this->courseOptions(),
            'batchLimit' => self::BATCH_LIMIT,
        ]);
    }

    /**
     * The download page: the certificates picked (ids / pairs), else every one
     * matching the filters, one per printed page.
     */
    public function download(): void
    {
        $filters = $this->filters();
        $payloads = [];
        if (Request::query('tab') === 'generated') {
            $ids = (array) Request::query('ids', []);
            $rows = $ids ? CertificateRegistry::issued(['ids' => $ids]) : CertificateRegistry::issued($filters + ['batch' => (string) Request::query('batch', '')]);
            foreach (array_slice($rows, 0, self::BATCH_LIMIT) as $row) {
                $payloads[] = CertificateRegistry::issuedPayload($row);
            }
        } else {
            $pairs = (array) Request::query('pairs', []);
            if (!$pairs) {
                $pairs = array_map(static fn(array $r): string => $r['user_id'] . '-' . $r['course_id'], CertificateRegistry::earned($filters));
            }
            foreach (array_slice($pairs, 0, self::BATCH_LIMIT) as $pair) {
                [$userId, $courseId] = array_map('intval', explode('-', (string) $pair) + [0, 0]);
                if ($payload = CertificateRegistry::earnedPayload($userId, $courseId)) {
                    $payloads[] = $payload;
                }
            }
        }
        View::render('partials.certificate-batch', [
            'payloads' => $payloads,
            'batchTitle' => 'Certificates (' . count($payloads) . ')',
            'backUrl' => '/admin/certificates' . (Request::query('tab') === 'generated' ? '?tab=generated' : ''),
        ]);
    }

    public function generateForm(): void
    {
        View::render('admin.certificates.generate', [
            'pageTitle' => 'Generate certificates',
            'activeNav' => 'certificates',
            'organisations' => $this->organisationOptions(),
        ]);
    }

    /** Makes one certificate per name, all at once, then opens them to download. */
    public function generate(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/certificates/generate');
        }
        // One person per line: "Full name" or "Full name, ID / reg. number".
        $people = [];
        foreach (preg_split('/\R/', (string) Request::post('names', '')) ?: [] as $line) {
            $parts = array_map('trim', explode(',', $line, 2));
            if (($parts[0] ?? '') !== '') {
                $people[] = ['name' => $parts[0], 'id_number' => $parts[1] ?? ''];
            }
        }
        $skills = trim((string) Request::post('skills', ''));
        $issuedOn = (string) Request::post('issued_on', date('Y-m-d'));
        if (!$people || $skills === '') {
            flashError('Enter at least one name and the skills / course the certificates are for.');
            redirect('/admin/certificates/generate');
        }
        if (count($people) > self::BATCH_LIMIT) {
            flashError('Generate at most ' . self::BATCH_LIMIT . ' certificates at a time.');
            redirect('/admin/certificates/generate');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedOn)) {
            $issuedOn = date('Y-m-d');
        }
        try {
            $batch = CertificateRegistry::issue($people, $skills, (int) Request::post('organisation_id', 0) ?: null, $issuedOn, (int) $this->admin['id']);
        } catch (\Throwable $e) {
            flashError('Generating certificates needs Super Admin to run the latest update first.');
            redirect('/admin/certificates/generate');
        }
        flashSuccess(count($people) . ' certificate' . (count($people) === 1 ? '' : 's') . ' generated.');
        redirect('/admin/certificates/download?tab=generated&batch=' . urlencode($batch));
    }

    private function filters(): array
    {
        $date = static fn(string $k): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) Request::query($k, '')) ? (string) Request::query($k) : '';
        return [
            'q' => trim((string) Request::query('q', '')),
            'organisation_id' => (int) Request::query('organisation_id', 0),
            'course_id' => (int) Request::query('course_id', 0),
            'from' => $date('from'),
            'to' => $date('to'),
        ];
    }

    private function organisationOptions(): array
    {
        try {
            return Database::connection()->query('SELECT id, name FROM organisations ORDER BY name')->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function courseOptions(): array
    {
        try {
            return Database::connection()->query(
                'SELECT c.id, c.title, c.organisation_id, o.name AS organisation_name FROM courses c
                 INNER JOIN organisations o ON o.id = c.organisation_id
                 WHERE c.certificate_enabled = 1 ORDER BY o.name, c.title'
            )->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
