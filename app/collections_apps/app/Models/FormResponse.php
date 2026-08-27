<?php

namespace App\Models;

use App\Core\Database;

class FormResponse
{
    public static function findForUserForm(int $userId, int $formId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM form_responses WHERE user_id = ? AND form_id = ?');
        $stmt->execute([$userId, $formId]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT fr.*, f.title, f.description, f.is_active
             FROM form_responses fr
             INNER JOIN forms f ON f.id = fr.form_id
             WHERE fr.user_id = ?
             ORDER BY f.title ASC'
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function save(int $userId, int $formId, array $answers): void
    {
        $pdo = Database::connection();
        $existing = self::findForUserForm($userId, $formId);
        $json = json_encode($answers);
        if ($existing) {
            $pdo->prepare('UPDATE form_responses SET answers = ?, submitted_at = NOW() WHERE id = ?')
                ->execute([$json, $existing['id']]);
            return;
        }
        $pdo->prepare('INSERT INTO form_responses (form_id, user_id, answers, submitted_at) VALUES (?, ?, ?, NOW())')
            ->execute([$formId, $userId, $json]);
    }

    public static function provisionForUser(int $userId, int $roleId): void
    {
        $forms = Form::forRole($roleId, true);
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO form_responses (form_id, user_id, answers) VALUES (?, ?, NULL)'
        );
        foreach ($forms as $form) {
            $stmt->execute([(int) $form['id'], $userId]);
        }
    }

    public static function provisionForForm(int $formId): void
    {
        $form = Form::find($formId);
        if ($form && ($form['purpose'] ?? 'profile') === 'course') {
            return;
        }
        $roleIds = Form::roleIds($formId);
        if (!$roleIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = Database::connection()->prepare("SELECT id FROM users WHERE role_id IN ($placeholders) AND is_active = 1");
        $stmt->execute($roleIds);
        $insert = Database::connection()->prepare(
            'INSERT IGNORE INTO form_responses (form_id, user_id, answers) VALUES (?, ?, NULL)'
        );
        foreach ($stmt->fetchAll() as $user) {
            $insert->execute([$formId, (int) $user['id']]);
        }
    }

    public static function completionStats(?int $organisationId = null): array
    {
        $sql = 'SELECT COUNT(*) AS total, SUM(CASE WHEN submitted_at IS NOT NULL THEN 1 ELSE 0 END) AS completed
                FROM form_responses fr
                INNER JOIN users u ON u.id = fr.user_id';
        $params = [];
        if ($organisationId) {
            $sql .= ' WHERE u.organisation_id = ?';
            $params[] = $organisationId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: ['total' => 0, 'completed' => 0];
        return [
            'total' => (int) $row['total'],
            'completed' => (int) $row['completed'],
        ];
    }

    public static function submittedForForm(int $formId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT fr.*, u.email, u.phone, u.first_name, u.last_name, u.role_id,
                    r.name AS role_name, o.name AS organisation_name
             FROM form_responses fr
             INNER JOIN users u ON u.id = fr.user_id
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE fr.form_id = ?
               AND fr.submitted_at IS NOT NULL
             ORDER BY fr.submitted_at DESC"
        );
        $stmt->execute([$formId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function countsByForm(): array
    {
        try {
            $rows = Database::connection()
                ->query(
                    "SELECT form_id,
                            COUNT(*) AS assigned,
                            SUM(CASE WHEN submitted_at IS NOT NULL THEN 1 ELSE 0 END) AS filled
                     FROM form_responses
                     GROUP BY form_id"
                )
                ->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['form_id']] = [
                'assigned' => (int) $row['assigned'],
                'filled' => (int) $row['filled'],
            ];
        }
        return $out;
    }

    public static function hydrate(array $row): array
    {
        $answers = $row['answers'] ?? null;
        if (is_string($answers) && $answers !== '') {
            $answers = json_decode($answers, true) ?: [];
        }
        $row['answers'] = is_array($answers) ? $answers : [];
        return $row;
    }
}
