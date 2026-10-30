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
            'site' => \App\Services\PageBuilder::site(),
            'smtp' => $smtp,
            'smtpHasPassword' => $smtpHasPassword,
        ]);
    }

    /** Sends a test email to this admin, to check the SMTP settings work. */
    public function testEmail(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/settings');
        }
        $to = trim((string) Request::post('test_email', '')) ?: (string) ($this->admin['email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flashError('Enter an email address to send the test to.');
            redirect('/admin/settings');
        }
        if (!MailerService::isConfigured()) {
            flashError('Save the SMTP settings first.');
            redirect('/admin/settings');
        }
        try {
            MailerService::sendNotice($to, 'Test email from ' . appName(), 'SMTP works', "This is a test email. Your SMTP settings are working, so students will get their sign-up, approval, enrolment and receipt emails.", 'Open the site', \App\Core\Url::absolute('/'));
            flashSuccess('Test email sent to ' . $to . '. Check the inbox (and spam).');
        } catch (\Throwable $e) {
            flashError('The test email failed: ' . $e->getMessage());
        }
        redirect('/admin/settings');
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

        // Hero image
        $currentHero = StoreSetting::get('hero_image');
        if (Request::post('remove_hero_image') && $currentHero) {
            UploadService::delete($currentHero);
            StoreSetting::set('hero_image', null);
            $currentHero = null;
        }
        $heroFile = Request::file('hero_image');
        if ($heroFile && !empty($heroFile['name'])) {
            try {
                $uploadedHero = UploadService::store($heroFile, 'settings');
                if ($currentHero) {
                    UploadService::delete($currentHero);
                }
                StoreSetting::set('hero_image', $uploadedHero);
            } catch (UploadException $e) {
                flashError('Hero image upload: ' . $e->getMessage());
                redirect('/admin/settings');
            }
        }

        $platformName = trim((string) Request::post('platform_name', ''));
        if ($platformName !== '') {
            StoreSetting::set('platform_name', $platformName);
        }

        // Contact details shown in the strip above the public navbar, the footer and the Contact page.
        if (Request::post('save_contact') === '1') {
            $site = \App\Services\PageBuilder::site();
            foreach (['contact_phone', 'contact_email', 'contact_whatsapp', 'contact_address', 'contact_hours'] as $key) {
                $site[$key] = trim((string) Request::post($key, ''));
            }
            $site['topbar_show'] = Request::post('topbar_show') === '1';
            \App\Services\PageBuilder::saveSite($site);
            flashSuccess('Contact details saved. They show above the navbar, in the footer and on the Contact page.');
            redirect('/admin/settings#contact-details');
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
        } elseif (Request::post('save_whatsapp') === '1') {
            $number = preg_replace('/[^0-9]/', '', (string) Request::post('whatsapp_share_number', ''));
            StoreSetting::set('whatsapp_share_number', $number !== '' ? $number : null);
            flashSuccess('WhatsApp sharing number saved.');
        } elseif (Request::post('save_final_grade') === '1') {
            $exam = max(0, min(100, (int) Request::post('final_grade_exam_percent', 90)));
            StoreSetting::set('final_grade_exam_percent', (string) $exam);
            flashSuccess('Final grade split saved: ' . $exam . '% exam + ' . (100 - $exam) . '% attachment.');
        } elseif (Request::post('save_course_payments') === '1') {
            StoreSetting::set('course_payments_enabled', Request::post('course_payments_enabled') === '1' ? '1' : '0');
            $provider = Request::post('course_payment_provider', 'mpesa') === 'stripe' ? 'stripe' : 'mpesa';
            $rate = (float) Request::post('course_ksh_usd_rate', 130);
            StoreSetting::set('course_payment_provider', $provider);
            StoreSetting::set('course_ksh_usd_rate', $rate > 0 ? (string) $rate : '130');
            StoreSetting::set('daraja_environment', Request::post('daraja_environment') === 'live' ? 'live' : 'sandbox');
            StoreSetting::set('daraja_shortcode', trim((string) Request::post('daraja_shortcode', '')) ?: null);
            StoreSetting::set('daraja_callback_url', trim((string) Request::post('daraja_callback_url', '')) ?: null);
            StoreSetting::set('stripe_publishable_key', trim((string) Request::post('stripe_publishable_key', '')) ?: null);
            foreach ([
                'daraja_consumer_key',
                'daraja_consumer_secret',
                'daraja_passkey',
                'stripe_secret_key',
                'stripe_webhook_secret',
            ] as $secretKey) {
                $value = trim((string) Request::post($secretKey, ''));
                if ($value !== '') {
                    StoreSetting::set($secretKey, $value);
                }
            }
            flashSuccess('Course payment settings saved.');
        } else {
            flashSuccess('Platform settings updated.');
        }
        redirect('/admin/settings');
    }
}
