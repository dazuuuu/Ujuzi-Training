<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\StoreSetting;
use App\Services\CertificateService;
use App\Services\DocumentLayout;
use App\Services\RecommendationLetterService;
use App\Services\UploadException;
use App\Services\UploadService;

class DocumentController extends BaseAdminController
{
    private const TYPES = ['certificate', 'recommendation_letter'];

    public function index(): void
    {
        $organisations = \App\Models\Organisation::providingCourses();
        $orgId = $this->orgId();
        $orgName = '';
        foreach ($organisations as $org) {
            if ((int) $org['id'] === $orgId) {
                $orgName = (string) $org['name'];
            }
        }
        $certificate = CertificateService::ownTemplatePath($orgId ?: null);
        View::render('admin.documents.index', [
            'pageTitle' => 'Documents',
            'activeNav' => 'documents',
            'organisations' => $organisations,
            'orgId' => $orgId,
            'orgName' => $orgName,
            'certificateTemplate' => $certificate,
            'certificateIsPdf' => $certificate ? CertificateService::isPdf($certificate) : false,
            'certificateIsImage' => $certificate ? CertificateService::isImage($certificate) : false,
            'letterTemplate' => RecommendationLetterService::templatePath(),
            'letterIsPdf' => RecommendationLetterService::isPdf(),
            'letterIsImage' => RecommendationLetterService::isImage(),
        ]);
    }

    /** The organisation whose certificate design is being edited (?org=), 0 for the platform-wide one. */
    private function orgId(): int
    {
        $id = (int) Request::query('org', 0);
        if ($id < 1) {
            return 0;
        }
        foreach (\App\Models\Organisation::providingCourses() as $org) {
            if ((int) $org['id'] === $id) {
                return $id;
            }
        }
        return 0;
    }

    public function update(string $type): void
    {
        $orgId = $type === 'certificate' ? $this->orgId() : 0;
        $back = '/admin/documents' . ($orgId ? '?org=' . $orgId : '');
        if ($orgId) {
            $this->updateOrganisationCertificate($orgId, $back);
        }
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

    /** Where each detail sits on the design, from the drag-and-drop editor. */
    public function saveLayout(string $type): void
    {
        if (!in_array($type, self::TYPES, true) || !csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/documents');
        }
        $orgId = $type === 'certificate' ? $this->orgId() : 0;
        $back = '/admin/documents' . ($orgId ? '?org=' . $orgId : '') . '#layout-' . $type;
        if (Request::post('reset') === '1') {
            DocumentLayout::reset($type, $orgId ?: null);
            flashSuccess('Positions reset to the defaults.');
            redirect($back);
        }
        $layout = json_decode((string) Request::post('layout', '{}'), true);
        DocumentLayout::save($type, is_array($layout) ? $layout : [], $orgId ?: null);
        flashSuccess('Positions saved. Printed documents now place the details exactly there.');
        redirect($back);
    }

    /** Uploads or removes one organisation's own certificate design. */
    private function updateOrganisationCertificate(int $orgId, string $back): never
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect($back);
        }
        if (Request::post('remove_template')) {
            CertificateService::setTemplate($orgId, null);
            flashSuccess('Design removed. This organisation\'s certificates use the platform-wide design again.');
            redirect($back);
        }
        $file = Request::file('template');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a PDF or image for the template.');
            redirect($back);
        }
        try {
            CertificateService::setTemplate($orgId, CertificateService::storeUpload($file));
            flashSuccess('Certificate design saved. This organisation\'s students get certificates on it.');
        } catch (UploadException $e) {
            flashError($e->getMessage());
        }
        redirect($back);
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
