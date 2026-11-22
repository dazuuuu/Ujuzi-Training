<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\BackupService;

/**
 * Export the whole database as a .sql file, or bring back rows from an
 * older one — without wiping or overwriting what's here now. Unlike the
 * "Restore a backup" step on /setup (which replaces every table), the
 * import here only adds rows this database is missing; see
 * BackupService::mergeImport() for exactly what it touches and what it
 * never does. Reachable only by a main Super Admin (owner) — it isn't in
 * App\Core\AdminAccess::PATH_SECTIONS, so BaseAdminController treats it
 * the same as the Admins page: owner-only, with no "tick this section"
 * option to hand it to a limited admin.
 */
class DataImportController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.data-import.index', [
            'pageTitle' => 'Data Import & Export',
            'activeNav' => 'data-import',
        ]);
    }

    /** Downloads the current database as one .sql file. */
    public function export(): void
    {
        BackupService::download(); // streams and exits
    }

    public function import(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/data-import');
        }

        $file = Request::file('backup');
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            flashError('Choose a .sql backup file, or a .zip containing one.');
            redirect('/admin/data-import');
        }

        $name = (string) ($file['name'] ?? '');
        try {
            if (preg_match('/\.zip$/i', $name)) {
                $sql = BackupService::sqlFromZip($file['tmp_name']);
                if ($sql === null) {
                    flashError('That zip has no .sql file in it.');
                    redirect('/admin/data-import');
                }
            } elseif (preg_match('/\.sql$/i', $name)) {
                $sql = (string) file_get_contents($file['tmp_name']);
            } else {
                flashError('That file is not a .sql backup or a .zip containing one.');
                redirect('/admin/data-import');
            }
            if (trim((string) $sql) === '') {
                flashError('That file was empty.');
                redirect('/admin/data-import');
            }

            $result = BackupService::mergeImport($sql);
        } catch (\Throwable $e) {
            flashError('Import failed: ' . $e->getMessage());
            redirect('/admin/data-import');
        }

        $summary = $result['inserted'] . ' row(s) added back across ' . $result['tables'] . ' table(s).'
            . ($result['skipped'] > 0 ? ' ' . $result['skipped'] . ' row(s) already existed and were left untouched.' : '')
            . ($result['failed'] > 0 ? ' ' . $result['failed'] . ' statement(s) could not run (shown below).' : '');
        if ($result['failed'] > 0) {
            $_SESSION['data_import_errors'] = $result['errors'];
            flashError($summary);
        } else {
            unset($_SESSION['data_import_errors']);
            flashSuccess($summary);
        }
        redirect('/admin/data-import');
    }
}
