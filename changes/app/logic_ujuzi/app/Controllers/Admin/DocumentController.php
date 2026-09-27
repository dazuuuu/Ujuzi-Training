<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\StoreSetting;
use App\Services\CertificateService;
use App\Services\RecommendationLetterService;
use App\Services\UploadException;
use App\Services\UploadService;

class DocumentController extends BaseAdminController
{
    private const TYPES = ['certificate', 'recommendation_letter'];

    public function index(): void
    {
        View::render('admin.documents.index', [
            'pageTitle' => 'Documents',
            'activeNav' => 'documents',
            'certificateTemplate' => CertificateService::templatePath(),
            'certificateIsPdf' => CertificateService::isPdf(),
            'certificateIsImage' => CertificateService::isImage(),
            'letterTemplate' => RecommendationLetterService::templatePath(),
            'letterIsPdf' => RecommendationLetterService::isPdf(),
            'letterIsImage' => RecommendationLetterService::isImage(),
        ]);
    }

    public function update(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            flashError('Unknown document type.');
            redirect('/admin/documents');
        }
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/documents');
        }

        $settingKey = $type . '_template';
        $current = StoreSetting::get($settingKey);

        if (Request::post('remove_template') && $current) {
            UploadService::delete($current);
            StoreSetting::set($settingKey, null);
            flashSuccess('Template removed.');
            redirect('/admin/documents');
        }

        $file = Request::file('template');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a PDF or image for the template.');
            redirect('/admin/documents');
        }

        try {
            $uploaded = self::storeTemplate($file, $type === 'certificate' ? 'certificates' : 'recommendation-letters');
            if ($current) {
                UploadService::delete($current);
            }
            StoreSetting::set($settingKey, $uploaded);
            flashSuccess($type === 'certificate'
                ? 'Certificate template saved. Completed-course certificates now use this design.'
                : 'Recommendation letter template saved. Approved attachments now use this design.');
        } catch (UploadException $e) {
            flashError($e->getMessage());
        }
        redirect('/admin/documents');
    }

    private static function storeTemplate(array $file, string $subdir): string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return UploadService::storeDocument($file, $subdir);
        }
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new UploadException('Upload a PDF or an image (JPG, PNG, WEBP, GIF) of the document design.');
        }
        return UploadService::store($file, $subdir);
    }
}
