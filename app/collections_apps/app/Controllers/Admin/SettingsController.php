<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\StoreSetting;
use App\Services\UploadException;
use App\Services\UploadService;

class SettingsController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.settings.index', [
            'pageTitle' => 'Settings',
            'activeNav' => 'settings',
            'settings' => StoreSetting::all(),
        ]);
    }

    public function update(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please resubmit the form.');
            redirect('/admin/settings');
        }

        $currentLogo = StoreSetting::get('store_logo');
        if (Request::post('remove_logo') && $currentLogo) {
            UploadService::delete($currentLogo);
            StoreSetting::set('store_logo', null);
            $currentLogo = null;
        }

        $file = Request::file('store_logo');
        if ($file && !empty($file['name'])) {
            try {
                $uploaded = UploadService::store($file, 'settings');
                if ($currentLogo) {
                    UploadService::delete($currentLogo);
                }
                StoreSetting::set('store_logo', $uploaded);
            } catch (UploadException $e) {
                flashError('Logo upload: ' . $e->getMessage());
                redirect('/admin/settings');
            }
        }

        $platformName = trim((string) Request::post('platform_name', ''));
        if ($platformName !== '') {
            StoreSetting::set('platform_name', $platformName);
        }

        flashSuccess('Platform settings updated.');
        redirect('/admin/settings');
    }
}
