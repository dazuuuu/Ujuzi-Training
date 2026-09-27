<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\StoreSetting;
use App\Services\CertificateService;
use App\Services\UploadException;
use App\Services\UploadService;

class CertificateController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.certificate.index', [
            'pageTitle' => 'Certificate',
            'activeNav' => 'certificate',
            'template' => CertificateService::templatePath(),
            'isPdf' => CertificateService::isPdf(),
            'isImage' => CertificateService::isImage(),
        ]);
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/certificate');
        }

        $current = CertificateService::templatePath();
        if (Request::post('remove_template') && $current) {
            UploadService::delete($current);
            StoreSetting::set('certificate_template', null);
            flashSuccess('Certificate template removed.');
            redirect('/admin/certificate');
        }

        $file = Request::file('template');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a PDF or image of how the certificate should look.');
            redirect('/admin/certificate');
        }

        try {
            $uploaded = self::storeTemplate($file);
            if ($current) {
                UploadService::delete($current);
            }
            StoreSetting::set('certificate_template', $uploaded);
            flashSuccess('Certificate template saved. Completed-course certificates now use this design, with the learner’s name and skills written on it.');
        } catch (UploadException $e) {
            flashError($e->getMessage());
        }
        redirect('/admin/certificate');
    }

    private static function storeTemplate(array $file): string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return UploadService::storeDocument($file, 'certificates');
        }
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new UploadException('Upload a PDF or an image (JPG, PNG, WEBP, GIF) of the certificate design.');
        }
        return UploadService::store($file, 'certificates');
    }
}
