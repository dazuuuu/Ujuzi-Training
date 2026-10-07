<?php

namespace App\Services;

use App\Core\Database;
use App\Core\LoginRoles;
use App\Core\Url;
use App\Models\OrganisationMembership;
use App\Models\Role;
use App\Models\User;

/**
 * Adding students to an organisation (and one of its branches): one at a
 * time or from the upload template. Each new student gets a login with the
 * default password (changed on first sign-in), is approved into the
 * organisation and branch straight away, and is emailed their details.
 */
class StudentRoster
{
    /** The upload template's columns, in order. */
    public const COLUMNS = ['first_name', 'other_names', 'last_name', 'email', 'phone'];

    public static function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, self::COLUMNS);
        fputcsv($out, ['Grace', 'Wanjiku', 'Mwangi', 'grace.mwangi@example.com', '0712345678']);
        fputcsv($out, ['Brian', '', 'Otieno', 'brian.otieno@example.com', '0722000111']);
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    /**
     * Adds one student. Returns null when created, else why not.
     * $fields: first_name, other_names, last_name, email, phone.
     */
    /** Roles a branch can add: students and the tutors who teach its courses. */
    public const ROLES = ['student' => 'Student', 'trainer' => 'Tutor'];

    public static function add(array $fields, int $organisationId, ?int $branchId, int $addedBy, string $roleSlug = 'student'): ?string
    {
        $first = trim((string) ($fields['first_name'] ?? ''));
        $last = trim((string) ($fields['last_name'] ?? ''));
        $email = strtolower(trim((string) ($fields['email'] ?? '')));
        $phone = User::normalizePhone((string) ($fields['phone'] ?? ''));
        if ($first === '' || $last === '') {
            return 'First and last name are needed.';
        }
        if ($email === '' && $phone === '') {
            return 'Give an email address or a phone number to sign in with.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email . ' is not a valid email address.';
        }
        if ($email !== '' && User::emailTaken($email)) {
            return $email . ' already has an account.';
        }
        if ($phone !== '' && User::phoneTaken($phone)) {
            return $phone . ' already has an account.';
        }
        $roleSlug = isset(self::ROLES[$roleSlug]) ? $roleSlug : 'student';
        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            return 'The ' . strtolower(self::ROLES[$roleSlug]) . ' role is missing. Ask Super Admin to run the updates.';
        }

        $id = User::create([
            'email' => $email,
            'phone' => $phone,
            'first_name' => $first,
            'last_name' => $last,
            'other_names' => trim((string) ($fields['other_names'] ?? '')),
            'role_id' => (int) $role['id'],
            'organisation_id' => $organisationId,
            'is_active' => 1,
            'password' => User::DEFAULT_PASSWORD,
            'must_change_password' => 1,
        ]);
        User::assignRegistrationNumber($id);
        OrganisationMembership::ensureApproved($id, $organisationId, $addedBy);
        if ($branchId) {
            Database::connection()->prepare('UPDATE organisation_memberships SET branch_id = ? WHERE user_id = ? AND organisation_id = ?')
                ->execute([$branchId, $id, $organisationId]);
        }
        if ($email !== '') {
            try {
                MailerService::sendAccountCreated($email, Url::absolute(LoginRoles::loginPath($roleSlug)), (string) $role['name'], User::DEFAULT_PASSWORD);
            } catch (MailerException $e) {
                // Created anyway: they can be given the default password in person.
            }
        }
        return null;
    }

    /**
     * Adds every row of an uploaded CSV (the template's columns; an older
     * "name, email" file works too). Returns [created, problems by row].
     */
    public static function import(string $path, int $organisationId, ?int $branchId, int $addedBy, string $roleSlug = 'student'): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [0, ['The file could not be read.']];
        }
        $header = fgetcsv($handle) ?: [];
        $header = array_map(static fn($c): string => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $header);
        $col = static fn(string $name) => array_search($name, $header, true);
        if ($col('email') === false && $col('phone') === false) {
            fclose($handle);
            return [0, ['Use the template: its first row must be the column names (' . implode(', ', self::COLUMNS) . ').']];
        }

        $created = 0;
        $problems = [];
        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (!array_filter($row, static fn($v) => trim((string) $v) !== '')) {
                continue;
            }
            $get = static fn(string $name): string => ($i = $col($name)) !== false ? trim((string) ($row[$i] ?? '')) : '';
            $fields = [
                'first_name' => $get('first_name'),
                'other_names' => $get('other_names'),
                'last_name' => $get('last_name'),
                'email' => $get('email'),
                'phone' => $get('phone'),
            ];
            if ($fields['first_name'] === '' && $get('name') !== '') {
                $parts = preg_split('/\s+/', $get('name'));
                $fields['first_name'] = array_shift($parts);
                $fields['last_name'] = (string) array_pop($parts);
                $fields['other_names'] = implode(' ', $parts);
            }
            $error = self::add($fields, $organisationId, $branchId, $addedBy, $roleSlug);
            if ($error === null) {
                $created++;
            } else {
                $problems[] = 'Row ' . $line . ': ' . $error;
            }
        }
        fclose($handle);
        return [$created, $problems];
    }
}
