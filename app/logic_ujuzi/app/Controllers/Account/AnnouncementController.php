<?php

namespace App\Controllers\Account;

use App\Core\Request;
use App\Models\Announcement;
use App\Services\UploadException;
use App\Services\UploadService;

/** Inbox for everyone; tutors, organisation admins and branch admins can also send. */
class AnnouncementController extends BaseAccountController
{
    public function index(): void
    {
        $this->render('account.announcements.index', [
            'pageTitle' => 'Announcements',
            'activeNav' => 'announcements',
            'inbox' => Announcement::forUser((int) $this->user['id']),
            'sent' => Announcement::canSend($this->user) ? Announcement::sentBy((int) $this->user['id']) : [],
            'canSend' => Announcement::canSend($this->user),
        ]);
    }

    public function create(): void
    {
        $this->requireSender();
        $this->render('account.announcements.create', [
            'pageTitle' => 'New announcement',
            'activeNav' => 'announcements',
            'branches' => Announcement::branchesFor($this->user),
            'categories' => Announcement::categoriesFor($this->user),
            'smsReady' => \App\Services\SmsService::configured(),
            'form' => ['title' => '', 'body' => ''],
        ]);
    }

    public function store(): void
    {
        $this->requireSender();
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/account/announcements/new');
        }
        $title = trim((string) Request::post('title', ''));
        $body = trim((string) Request::post('body', ''));
        $branchIds = array_map('intval', (array) Request::post('branch_ids', []));
        $categoryIds = array_map('intval', (array) Request::post('category_ids', []));
        $path = $name = '';
        $file = Request::file('attachment');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                flashError('Attach a PDF, JPG or PNG file.');
                redirect('/account/announcements/new');
            }
            try {
                $path = UploadService::storeDocument($file, 'announcements');
                $name = mb_substr((string) $file['name'], 0, 255);
            } catch (UploadException $e) {
                flashError($e->getMessage());
                redirect('/account/announcements/new');
            }
        }
        if ($title === '' || ($body === '' && $path === '')) {
            flashError('Give the announcement a title and a message or a file.');
            redirect('/account/announcements/new');
        }
        $recipients = Announcement::audience($this->user, $branchIds, $categoryIds);
        if (!$recipients) {
            flashError('Nobody matches that audience. Check the branch and category you chose.');
            redirect('/account/announcements/new');
        }
        $email = Request::post('notify_email') === '1';
        $sms = Request::post('notify_sms') === '1';
        $id = Announcement::create($this->user, [
            'title' => $title, 'body' => $body, 'attachment_path' => $path, 'attachment_name' => $name,
            'branch_ids' => $branchIds, 'category_ids' => $categoryIds, 'notify_email' => $email, 'notify_sms' => $sms,
        ], $recipients);
        Announcement::notify($recipients, $email, $sms, 'You have a new confidential message: ' . $title . '.', '/account/announcements/' . $id);
        flashSuccess('Announcement sent to ' . count($recipients) . ' ' . (count($recipients) === 1 ? 'person' : 'people') . '.' . ($email || $sms ? ' Email/SMS alerts were sent.' : ''));
        redirect('/account/announcements/' . $id);
    }

    public function show(string $id): void
    {
        $a = Announcement::openFor((int) $id, (int) $this->user['id']);
        if (!$a) {
            flashError('That announcement could not be found.');
            redirect('/account/announcements');
        }
        $this->render('account.announcements.show', ['pageTitle' => $a['title'], 'activeNav' => 'announcements', 'a' => $a]);
    }

    private function requireSender(): void
    {
        if (!Announcement::canSend($this->user)) {
            flashError('Only tutors and organisation or branch admins can send announcements.');
            redirect('/account/announcements');
        }
    }
}
