<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByIdentifier(string $type, string $value): ?array
    {
        $column = $type === 'email' ? 'email' : 'phone';
        $stmt = Database::connection()->prepare(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.$column = ?"
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function all(?int $organisationId = null, ?int $roleId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                       r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id
                WHERE 1=1';
        $params = [];
        if ($organisationId) {
            $sql .= ' AND u.organisation_id = ?';
            $params[] = $organisationId;
        }
        if ($roleId) {
            $sql .= ' AND u.role_id = ?';
            $params[] = $roleId;
        }
        $sql .= ' ORDER BY u.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function allForOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.organisation_id = ?
                OR EXISTS (
                    SELECT 1 FROM organisation_memberships m
                    WHERE m.user_id = u.id
                      AND m.organisation_id = ?
                      AND m.status = \'approved\'
                )
             ORDER BY u.created_at DESC'
        );
        $stmt->execute([$organisationId, $organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function studentsForAttachmentProvider(int $providerUserId): array
    {
        $studentRole = Role::findBySlug('student');
        if (!$studentRole) {
            return [];
        }
        $students = self::all(null, (int) $studentRole['id']);
        $forms = Form::forRole((int) $studentRole['id'], true, 'profile');
        $fields = [];
        foreach ($forms as $form) {
            foreach (FormField::forForm((int) $form['id']) as $field) {
                if (($field['field_type'] ?? '') === 'attachment_provider') {
                    $fields[] = $field['field_key'];
                }
            }
        }
        if (!$fields) {
            return [];
        }
        $matched = [];
        foreach ($students as $student) {
            foreach (FormResponse::forUser((int) $student['id']) as $response) {
                foreach ($fields as $key) {
                    $value = $response['answers'][$key] ?? '';
                    $values = is_array($value) ? $value : [$value];
                    if (in_array($providerUserId, array_map('intval', $values), true)) {
                        $matched[] = $student;
                        continue 3;
                    }
                }
            }
        }
        return $matched;
    }

    public static function attachmentTrainersForOrganisations(array $organisationIds): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds))));
        if (!$organisationIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE r.slug = 'attachment_trainer'
               AND u.is_active = 1
               AND (
                    u.organisation_id IN ($placeholders)
                    OR EXISTS (
                        SELECT 1 FROM organisation_memberships m
                        WHERE m.user_id = u.id
                          AND m.organisation_id IN ($placeholders)
                          AND m.status = 'approved'
                    )
               )
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute(array_merge($organisationIds, $organisationIds));
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function searchAttachmentProviders(string $query = '', int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $query = trim($query);
        $sql = "SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id
                WHERE r.slug = 'attachment_trainer' AND u.is_active = 1";
        $params = [];
        if ($query !== '') {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.name LIKE ?)";
            $like = '%' . $query . '%';
            $params = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC LIMIT ' . $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function validActiveAttachmentProviderIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1 AND r.slug = 'attachment_trainer' AND u.id IN ($placeholders)"
        );
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function namesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, first_name, last_name, email FROM users WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $label = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $names[(int) $row['id']] = $label !== '' ? $label : (string) ($row['email'] ?? ('User #' . $row['id']));
        }
        return $names;
    }

    public static function attachmentDuration(array $user): string
    {
        try {
            $forms = Form::forRole((int) $user['role_id'], true, 'profile');
        } catch (\Throwable $e) {
            return '';
        }
        foreach ($forms as $form) {
            $fields = FormField::forForm((int) $form['id']);
            $response = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
            $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
            foreach ($fields as $field) {
                if (($field['field_type'] ?? '') !== 'duration') {
                    continue;
                }
                $label = FormField::formatAnswer($field, $answers[$field['field_key']] ?? null);
                if ($label !== '' && $label !== '—') {
                    return $label;
                }
            }
        }
        return '';
    }

    public static function setOrganisationId(int $id, int $organisationId): void
    {
        Database::connection()
            ->prepare('UPDATE users SET organisation_id = ? WHERE id = ? AND organisation_id IS NULL')
            ->execute([$organisationId, $id]);
    }

    public static function assignOrganisation(int $id, ?int $organisationId): void
    {
        Database::connection()
            ->prepare('UPDATE users SET organisation_id = ? WHERE id = ?')
            ->execute([$organisationId && $organisationId > 0 ? $organisationId : null, $id]);
    }

    public static function updateProfileNames(int $id, string $firstName, string $lastName): void
    {
        Database::connection()
            ->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE id = ?')
            ->execute([$firstName !== '' ? $firstName : null, $lastName !== '' ? $lastName : null, $id]);
    }

    public static function countByRole(): array
    {
        return Database::connection()->query(
            'SELECT r.slug, r.name, COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id, r.slug, r.name
             ORDER BY r.sort_order ASC'
        )->fetchAll();
    }

    public static function count(?int $organisationId = null): int
    {
        if ($organisationId) {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE organisation_id = ?');
            $stmt->execute([$organisationId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function recent(int $limit = 8, ?int $organisationId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id';
        $params = [];
        if ($organisationId) {
            $sql .= ' WHERE u.organisation_id = ?';
            $params[] = $organisationId;
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $email = trim((string) ($fields['email'] ?? ''));
        $phone = trim((string) ($fields['phone'] ?? ''));
        $passwordHash = null;
        if (!empty($fields['password'])) {
            $passwordHash = password_hash((string) $fields['password'], PASSWORD_DEFAULT);
        } elseif (!empty($fields['password_hash'])) {
            $passwordHash = (string) $fields['password_hash'];
        }
        $pdo->prepare(
            'INSERT INTO users (role_id, organisation_id, email, password_hash, phone, first_name, last_name, is_active, dashboard_created_at, profile_created_at, email_verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $email !== '' ? $email : null,
            $passwordHash,
            $phone !== '' ? $phone : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            isset($fields['is_active']) && !$fields['is_active'] ? 0 : 1,
            !empty($fields['email_verified_at']) ? date('Y-m-d H:i:s') : null,
        ]);
        $id = (int) $pdo->lastInsertId();
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
        return $id;
    }

    public static function update(int $id, array $fields): void
    {
        $status = (string) ($fields['account_status'] ?? (!empty($fields['is_active']) ? 'active' : 'blocked'));
        if (!in_array($status, ['active', 'blocked', 'suspended'], true)) {
            $status = !empty($fields['is_active']) ? 'active' : 'blocked';
        }
        Database::connection()->prepare(
            'UPDATE users SET role_id = ?, organisation_id = ?, email = ?, phone = ?, first_name = ?, last_name = ?, is_active = ?, account_status = ? WHERE id = ?'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $fields['email'] !== '' ? $fields['email'] : null,
            $fields['phone'] !== '' ? $fields['phone'] : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            $status,
            $id,
        ]);
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['active', 'blocked', 'suspended'], true)) {
            throw new \InvalidArgumentException('Invalid user status.');
        }
        Database::connection()->prepare(
            'UPDATE users SET account_status = ?, is_active = ? WHERE id = ?'
        )->execute([$status, $status === 'active' ? 1 : 0, $id]);
    }

    public static function updateCredentials(int $id, ?string $email, ?string $phone): void
    {
        Database::connection()->prepare(
            'UPDATE users SET email = ?, phone = ? WHERE id = ?'
        )->execute([
            $email !== null && $email !== '' ? strtolower(trim($email)) : null,
            $phone !== null && $phone !== '' ? self::normalizePhone($phone) : null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        try {
            Database::connection()->prepare(
                "DELETE FROM organisation_branches WHERE owner_type = 'attachment_provider' AND owner_user_id = ?"
            )->execute([$id]);
        } catch (\Throwable $e) {
            // Branch ownership is added by the branch ownership migration.
        }
        Database::connection()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public static function markEmailVerified(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
            ->execute([$id]);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([$id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::connection()
            ->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function verifyPassword(string $email, string $password): ?array
    {
        $user = self::findByIdentifier('email', strtolower(trim($email)));
        if (!$user || empty($user['has_password'])) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $hash = (string) $stmt->fetchColumn();
        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }
        return $user;
    }

    public static function passwordError(string $password, string $confirm = ''): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if ($confirm !== '' && $password !== $confirm) {
            return 'Password confirmation does not match.';
        }
        return null;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    public static function hydrate(array $row): array
    {
        $managed = $row['managed_role_slugs'] ?? '[]';
        if (is_string($managed)) {
            $managed = json_decode($managed, true) ?: [];
        }
        $row['managed_role_slugs'] = array_values(array_filter((array) $managed));
        $row['has_admin_features'] = !empty($row['has_admin_features']);
        $row['is_under_organisation'] = !empty($row['is_under_organisation']);
        $row['can_manage_users'] = !empty($row['can_manage_users']);
        $row['has_password'] = !empty($row['password_hash']);
        $row['account_status'] = (string) ($row['account_status'] ?? (!empty($row['is_active']) ? 'active' : 'blocked'));
        unset($row['password_hash']);
        return $row;
    }
}
