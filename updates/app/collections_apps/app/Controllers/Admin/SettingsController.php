<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\StoreSetting;
use App\Services\MailerService;
use App\Services\UploadException;
use App\Services\UploadService;

class SettingsController extends BaseAdminController
{
    public function index(): void
    {
        $smtp = MailerService::config();
        $smtpHasPassword = $smtp['password'] !== '';
        unset($smtp['password']);

        View::render('admin.settings.index', [
            'pageTitle' => 'Settings',
            'activeNav' => 'settings',
            'settings' => StoreSetting::all(),
            'smtpConfigured' => MailerService::isConfigured(),
            'smtp' => $smtp,
            'smtpHasPassword' => $smtpHasPassword,
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

        if (Request::post('save_smtp') === '1') {
            $mailHost = trim((string) Request::post('mail_host', ''));
            StoreSetting::set('mail_host', $mailHost !== '' ? $mailHost : null);
            StoreSetting::set('mail_port', trim((string) Request::post('mail_port', '587')) ?: '587');
            $encryption = Request::post('mail_encryption', 'tls') === 'ssl' ? 'ssl' : 'tls';
            StoreSetting::set('mail_encryption', $encryption);
            StoreSetting::set('mail_username', trim((string) Request::post('mail_username', '')) ?: null);
            $mailPassword = (string) Request::post('mail_password', '');
            if ($mailPassword !== '') {
                StoreSetting::set('mail_password', $mailPassword);
            }
            StoreSetting::set('mail_from_address', trim((string) Request::post('mail_from_address', '')) ?: null);
            StoreSetting::set('mail_from_name', trim((string) Request::post('mail_from_name', '')) ?: null);
            flashSuccess('SMTP credentials saved. You can now email registration forms to clients.');
        } else {
            flashSuccess('Platform settings updated.');
        }
        redirect('/admin/settings');
    }
}
