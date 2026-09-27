<?php

namespace App\Controllers\Admin;

use App\Core\AdminAccess;
use App\Core\Request;
use App\Core\View;
use App\Models\Admin;

/**
 * Owners (main Super Admins) add other Super Admins and choose what each may
 * open. BaseAdminController already keeps everyone but owners out of here.
 */
class AdminController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.admins.index', [
            'pageTitle' => 'Admins',
            'activeNav' => 'admins',
            'admins' => Admin::all(),
            'sections' => AdminAccess::SECTIONS,
            'me' => $this->admin,
        ]);
    }

    public function store(): void
    {
        $this->csrf();
        $email = strtolower(trim((string) Request::post('email', '')));
        $name = trim((string) Request::post('name', ''));
        $password = (string) Request::post('password', '');
        $owner = Request::post('is_owner') === '1';
        $permissions = AdminAccess::clean((array) Request::post('permissions', []));

        $error = match (true) {
            !filter_var($email, FILTER_VALIDATE_EMAIL) => 'Enter a valid email address.',
            Admin::findByEmail($email) !== null => 'An admin with that email already exists.',
            mb_strlen($password) < 8 => 'Use a password of at least 8 characters.',
            !$owner && !$permissions => 'Tick at least one section this admin may use.',
            default => null,
        };
        if ($error) {
            flashError($error);
            redirect('/admin/admins');
        }

        Admin::createLimited($email, $password, $name, $owner ? [] : $permissions, $owner, (int) $this->admin['id']);
        flashSuccess(($name ?: $email) . ' can now sign in at the Super Admin login' . ($owner ? ' with full access.' : ' with the sections you ticked.'));
        redirect('/admin/admins');
    }

    public function update(string $id): void
    {
        $this->csrf();
        $target = $this->target((int) $id);
        $owner = Request::post('is_owner') === '1';
        $active = Request::post('is_active', '1') === '1';
        $permissions = AdminAccess::clean((array) Request::post('permissions', []));

        $losesOwner = $target['is_owner'] && $target['is_active'] && (!$owner || !$active);
        if ($losesOwner && Admin::activeOwnerCount() <= 1) {
            flashError('There must always be at least one active main Super Admin.');
            redirect('/admin/admins');
        }
        if ($target['id'] === (int) $this->admin['id'] && (!$owner || !$active)) {
            flashError('You cannot remove your own full access or deactivate yourself.');
            redirect('/admin/admins');
        }
        if (!$owner && !$permissions && $active) {
            flashError('Tick at least one section, or deactivate the admin instead.');
            redirect('/admin/admins');
        }

        Admin::updateAccess($target['id'], trim((string) Request::post('name', '')), $owner ? [] : $permissions, $owner, $active);
        $newPassword = (string) Request::post('password', '');
        if ($newPassword !== '') {
            if (mb_strlen($newPassword) < 8) {
                flashError('Access saved, but the new password was too short (8 characters minimum) and was not changed.');
                redirect('/admin/admins');
            }
            Admin::setPassword($target['id'], $newPassword);
        }
        flashSuccess('Saved ' . ($target['name'] ?: $target['email']) . '.');
        redirect('/admin/admins');
    }

    public function destroy(string $id): void
    {
        $this->csrf();
        $target = $this->target((int) $id);
        if ($target['id'] === (int) $this->admin['id']) {
            flashError('You cannot remove your own account.');
            redirect('/admin/admins');
        }
        if ($target['is_owner'] && $target['is_active'] && Admin::activeOwnerCount() <= 1) {
            flashError('There must always be at least one active main Super Admin.');
            redirect('/admin/admins');
        }
        Admin::delete($target['id']);
        flashSuccess('Removed ' . ($target['name'] ?: $target['email']) . '.');
        redirect('/admin/admins');
    }

    private function target(int $id): array
    {
        $row = Admin::find($id);
        if (!$row) {
            flashError('That admin was not found.');
            redirect('/admin/admins');
        }
        return Admin::hydrate($row);
    }

    private function csrf(): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/admins');
        }
    }
}
