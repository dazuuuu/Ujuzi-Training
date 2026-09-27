<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\HomepageContent;
use App\Services\UploadException;
use App\Services\UploadService;

/**
 * Super Admin edits the homepage's words, its "Trusted by" logos, and which
 * courses it features — never its layout.
 */
class HomepageController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.homepage.index', [
            'pageTitle' => 'Homepage',
            'activeNav' => 'homepage',
            'fields' => HomepageContent::FIELDS,
            'logos' => HomepageContent::logos(),
            'courses' => HomepageContent::publishedCourses(),
        ]);
    }

    public function saveContent(): void
    {
        $this->csrf();
        HomepageContent::save((array) Request::post('content', []));
        flashSuccess('Homepage text saved. Blank fields went back to their original wording.');
        redirect('/admin/homepage');
    }

    public function saveCourses(): void
    {
        $this->csrf();
        $count = HomepageContent::setFeatured((array) Request::post('course_ids', []));
        flashSuccess($count ? $count . ' course' . ($count === 1 ? '' : 's') . ' now show on the homepage.' : 'No courses are featured — the homepage course section is hidden until you tick some.');
        redirect('/admin/homepage#courses');
    }

    public function addLogo(): void
    {
        $this->csrf();
        $name = trim((string) Request::post('name', ''));
        $file = Request::file('logo');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flashError('Choose a logo image to upload.');
            redirect('/admin/homepage#logos');
        }
        try {
            $path = UploadService::store($file, 'logos');
        } catch (UploadException $e) {
            flashError($e->getMessage());
            redirect('/admin/homepage#logos');
        }
        $logos = HomepageContent::logos();
        $logos[] = ['name' => mb_substr($name, 0, 80), 'image' => $path];
        HomepageContent::saveLogos($logos);
        flashSuccess('Logo added.');
        redirect('/admin/homepage#logos');
    }

    public function removeLogo(string $index): void
    {
        $this->csrf();
        $logos = HomepageContent::logos();
        $i = (int) $index;
        if (isset($logos[$i])) {
            UploadService::delete($logos[$i]['image'] ?? null);
            array_splice($logos, $i, 1);
            HomepageContent::saveLogos($logos);
            flashSuccess('Logo removed.');
        }
        redirect('/admin/homepage#logos');
    }

    public function moveLogo(string $index, string $direction): void
    {
        $this->csrf();
        $logos = HomepageContent::logos();
        $i = (int) $index;
        $j = $direction === 'up' ? $i - 1 : $i + 1;
        if (isset($logos[$i], $logos[$j])) {
            [$logos[$i], $logos[$j]] = [$logos[$j], $logos[$i]];
            HomepageContent::saveLogos($logos);
        }
        redirect('/admin/homepage#logos');
    }

    private function csrf(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/homepage');
        }
    }
}
