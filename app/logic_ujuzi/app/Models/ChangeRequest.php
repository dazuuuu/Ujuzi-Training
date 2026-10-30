<?php

namespace App\Models;

use App\Core\Database;

/**
 * A request to change locked account details — names, sign-in email, or the
 * National ID / passport / birth certificate number. Nothing changes until
 * Super Admin approves; approving applies the new values (still refusing any
 * that another account already uses).
 */
class ChangeRequest
{
    /** Field key => label. */
    public const FIELDS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'other_names' => 'Other names',
        'email' => 'Email address',
        'id_type' => 'ID document',
        'id_number' => 'ID / birth certificate number',
    ];

    /** The user's current ID document answer: ['type' => ..., 'number' => ...] (empty strings when none). */
    public static function currentIdDocument(int $userId): array
    {
        foreach (self::idDocumentAnswers($userId) as [, , $value]) {
            return ['type' => (string) ($value['type'] ?? ''), 'number' => (string) ($value['number'] ?? '')];
        }
        return ['type' => '', 'number' => ''];
    }

    /** The current value of every changeable field. */
    public static function currentValues(array $user): array
    {
        $doc = self::currentIdDocument((int) $user['id']);
        return [
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
            'other_names' => (string) ($user['other_names'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'id_type' => $doc['type'],
            'id_number' => $doc['number'],
        ];
    }

    /**
     * Files a request with only the fields that differ. Returns null when
     * sent, else why not.
     */
    public static function submit(array $user, array $posted, string $reason): ?string
    {
        $userId = (int) $user['id'];
        if (self::pendingFor($userId)) {
            return 'You already have a change request waiting for Super Admin. Wait for it to be decided first.';
        }
        $current = self::currentValues($user);
        $changes = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $new = trim(preg_replace('/\s+/', ' ', (string) ($posted[$key] ?? '')));
            if ($key === 'email') {
                $new = strtolower($new);
            }
            if ($new !== '' && $new !== $current[$key]) {
                $changes[$key] = ['from' => $current[$key], 'to' => $new];
            }
        }
        if (!$changes) {
            return 'Type the new value of at least one detail you want changed.';
        }
        if (isset($changes['email']) && !filter_var($changes['email']['to'], FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        if (isset($changes['id_type']) && !in_array($changes['id_type']['to'], FormFieldTypes::idDocumentTypes(), true)) {
            return 'Choose National ID, passport or birth certificate.';
        }
        if (isset($changes['id_number']) && !preg_match('/^[A-Za-z0-9 \/-]{4,30}$/', $changes['id_number']['to'])) {
            return 'Enter a valid document number.';
        }
        if (trim($reason) === '') {
            return 'Say why the details need changing, so Super Admin can check.';
        }
        if ($error = self::duplicateError($userId, $current, $changes)) {
            return $error;
        }
        Database::connection()->prepare('INSERT INTO user_change_requests (user_id, changes, reason) VALUES (?, ?, ?)')
            ->execute([$userId, json_encode($changes, JSON_UNESCAPED_UNICODE), mb_substr(trim($reason), 0, 500)]);
        return null;
    }

    /** Why the new values clash with another account, or null. */
    private static function duplicateError(int $userId, array $current, array $changes): ?string
    {
        $value = static fn(string $key): string => $changes[$key]['to'] ?? $current[$key];
        if (isset($changes['email']) && User::emailTaken($changes['email']['to'], $userId)) {
            return 'An account already exists in the system with the email ' . $changes['email']['to'] . '.';
        }
        if ((isset($changes['first_name']) || isset($changes['last_name']) || isset($changes['other_names']))
            && User::nameTaken($value('first_name'), $value('last_name'), $value('other_names'), $userId)) {
            return 'An account already exists in the system with the name ' . trim($value('first_name') . ' ' . $value('other_names') . ' ' . $value('last_name')) . '.';
        }
        if (isset($changes['id_number']) && User::idNumberTaken($changes['id_number']['to'], $userId)) {
            return 'An account already exists in the system with the document number ' . $changes['id_number']['to'] . '.';
        }
        return null;
    }

    public static function pendingFor(int $userId): ?array
    {
        try {
            $stmt = Database::connection()->prepare("SELECT * FROM user_change_requests WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            return null;
        }
        return $row ? self::hydrate($row) : null;
    }

    /** The user's latest decided request (to tell them how it went). */
    public static function latestDecidedFor(int $userId): ?array
    {
        try {
            $stmt = Database::connection()->prepare("SELECT * FROM user_change_requests WHERE user_id = ? AND status <> 'pending' ORDER BY decided_at DESC LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            return null;
        }
        return $row ? self::hydrate($row) : null;
    }

    public static function pendingCount(): int
    {
        return (int) Database::connection()->query("SELECT COUNT(*) FROM user_change_requests WHERE status = 'pending'")->fetchColumn();
    }

    /** Requests for Super Admin, newest first; status '' = all. */
    public static function list(string $status = 'pending'): array
    {
        $sql = "SELECT cr.*, u.first_name, u.last_name, u.other_names, u.email, u.registration_number, r.name AS role_name
                FROM user_change_requests cr
                INNER JOIN users u ON u.id = cr.user_id
                LEFT JOIN roles r ON r.id = u.role_id";
        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE cr.status = ?';
            $params[] = $status;
        }
        $stmt = Database::connection()->prepare($sql . ' ORDER BY cr.created_at DESC LIMIT 300');
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM user_change_requests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** Approves (applying the changes) or rejects. Returns null when done, else why not. */
    public static function decide(int $id, bool $approve, int $adminId, string $note = ''): ?string
    {
        $request = self::find($id);
        if (!$request || $request['status'] !== 'pending') {
            return 'That request was already decided.';
        }
        if ($approve) {
            $user = User::find((int) $request['user_id']);
            if (!$user) {
                return 'That account no longer exists.';
            }
            $current = self::currentValues($user);
            $changes = $request['changes'];
            if ($error = self::duplicateError((int) $user['id'], $current, $changes)) {
                return $error . ' The request was not applied.';
            }
            self::apply($user, $current, $changes);
        }
        Database::connection()->prepare('UPDATE user_change_requests SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?')
            ->execute([$approve ? 'approved' : 'rejected', trim($note) !== '' ? mb_substr(trim($note), 0, 500) : null, $adminId, $id]);
        try {
            \App\Services\Notifier::toUser(
                (int) $request['user_id'],
                'Your detail change request was ' . ($approve ? 'approved' : 'declined'),
                $approve ? 'Your details were updated' : 'Your details were not changed',
                $approve ? 'Super Admin approved your request and your account now shows the new details.' : 'Super Admin declined your request.' . (trim($note) !== '' ? ' Note: ' . trim($note) : ''),
                'Open my profile',
                '/account/profile'
            );
        } catch (\Throwable $e) {
            // Mail is optional.
        }
        return null;
    }

    private static function apply(array $user, array $current, array $changes): void
    {
        $userId = (int) $user['id'];
        $to = static fn(string $key): string => $changes[$key]['to'] ?? $current[$key];
        if (isset($changes['first_name']) || isset($changes['last_name']) || isset($changes['other_names'])) {
            User::updateProfileNames($userId, $to('first_name'), $to('last_name'), $to('other_names'));
            // Keep the name on the forms in step with the account.
            foreach (self::answersOfType($userId, 'name') as [$formId, $key, $value, $answers]) {
                $answers[$key] = ['first' => $to('first_name'), 'last' => $to('last_name'), 'other' => $to('other_names')];
                FormResponse::save($userId, $formId, $answers);
            }
        }
        if (isset($changes['email'])) {
            User::updateCredentials($userId, $changes['email']['to'], (string) ($user['phone'] ?? ''));
            foreach (self::answersOfType($userId, 'email') as [$formId, $key, $value, $answers]) {
                $answers[$key] = $changes['email']['to'];
                FormResponse::save($userId, $formId, $answers);
            }
        }
        if (isset($changes['id_type']) || isset($changes['id_number'])) {
            foreach (self::idDocumentAnswers($userId) as [$formId, $key, $value, $answers]) {
                $answers[$key] = ['type' => $to('id_type'), 'number' => $to('id_number')];
                FormResponse::save($userId, $formId, $answers);
            }
        }
    }

    /** [[form id, field key, value, all answers], ...] for the user's ID document answers. */
    private static function idDocumentAnswers(int $userId): array
    {
        return self::answersOfType($userId, 'id_document');
    }

    private static function answersOfType(int $userId, string $type): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT fr.form_id, fr.answers, ff.field_key FROM form_responses fr
                 INNER JOIN form_fields ff ON ff.form_id = fr.form_id AND ff.field_type = ?
                 WHERE fr.user_id = ?'
            );
            $stmt->execute([$type, $userId]);
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $answers = json_decode((string) $row['answers'], true);
            if (!is_array($answers)) {
                continue;
            }
            $value = $answers[$row['field_key']] ?? null;
            if ($type === 'id_document' && !is_array($value)) {
                continue;
            }
            $out[] = [(int) $row['form_id'], (string) $row['field_key'], $value, $answers];
        }
        return $out;
    }

    private static function hydrate(array $row): array
    {
        $row['changes'] = json_decode((string) $row['changes'], true) ?: [];
        return $row;
    }
}
