<?php

namespace App\Models;

use App\Core\Database;

class Form
{
    public static function all(): array
    {
        $rows = Database::connection()
            ->query('SELECT * FROM forms ORDER BY created_at DESC')
            ->fetchAll();
        return array_map([self::class, 'withRoles'], $rows);
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM forms')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::withRoles($row) : null;
    }

    public static function forRole(int $roleId, bool $activeOnly = true, string $purpose = 'profile'): array
    {
        $sql = 'SELECT f.* FROM forms f
                INNER JOIN form_roles fr ON fr.form_id = f.id
                WHERE fr.role_id = ?';
        $params = [$roleId];
        if ($activeOnly) {
            $sql .= ' AND f.is_active = 1';
        }
        if ($purpose !== '') {
            $sql .= ' AND COALESCE(f.purpose, \'profile\') = ?';
            $params[] = $purpose;
        }
        $sql .= ' ORDER BY f.title ASC';
        try {
            $stmt = Database::connection()->prepare($sql);
            $stmt->execute($params);
            return array_map([self::class, 'withRoles'], $stmt->fetchAll());
        } catch (\Throwable $e) {
            $fallback = 'SELECT f.* FROM forms f INNER JOIN form_roles fr ON fr.form_id = f.id WHERE fr.role_id = ?';
            if ($activeOnly) {
                $fallback .= ' AND f.is_active = 1';
            }
            $fallback .= ' ORDER BY f.title ASC';
            $stmt = Database::connection()->prepare($fallback);
            $stmt->execute([$roleId]);
            return array_map([self::class, 'withRoles'], $stmt->fetchAll());
        }
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $purpose = ($fields['purpose'] ?? '') === 'course' ? 'course' : 'profile';
        $pdo->prepare('INSERT INTO forms (title, description, is_active, purpose, created_by_admin_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $fields['title'],
                $fields['description'] ?? null,
                !empty($fields['is_active']) ? 1 : 0,
                $purpose,
                $fields['created_by_admin_id'] ?? null,
            ]);
        $id = (int) $pdo->lastInsertId();
        self::syncRoles($id, $fields['role_ids'] ?? []);
        return $id;
    }

    public static function update(int $id, array $fields): void
    {
        $purpose = ($fields['purpose'] ?? '') === 'course' ? 'course' : 'profile';
        Database::connection()->prepare(
            'UPDATE forms SET title = ?, description = ?, is_active = ?, purpose = ? WHERE id = ?'
        )->execute([
            $fields['title'],
            $fields['description'] ?? null,
            !empty($fields['is_active']) ? 1 : 0,
            $purpose,
            $id,
        ]);
        self::syncRoles($id, $fields['role_ids'] ?? []);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM forms WHERE id = ?')->execute([$id]);
    }

    public static function syncRoles(int $formId, array $roleIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM form_roles WHERE form_id = ?')->execute([$formId]);
        $stmt = $pdo->prepare('INSERT INTO form_roles (form_id, role_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            if ($roleId > 0) {
                $stmt->execute([$formId, $roleId]);
            }
        }
        FormResponse::provisionForForm($formId);
    }

    public static function roleIds(int $formId): array
    {
        $stmt = Database::connection()->prepare('SELECT role_id FROM form_roles WHERE form_id = ?');
        $stmt->execute([$formId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    private static function withRoles(array $row): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.id, r.slug, r.name
             FROM form_roles fr
             INNER JOIN roles r ON r.id = fr.role_id
             WHERE fr.form_id = ?
             ORDER BY r.sort_order ASC'
        );
        $stmt->execute([(int) $row['id']]);
        $row['roles'] = $stmt->fetchAll();
        $row['role_ids'] = array_map(fn($role) => (int) $role['id'], $row['roles']);
        $row['field_count'] = FormField::countForForm((int) $row['id']);
        $row['purpose'] = ($row['purpose'] ?? '') === 'course' ? 'course' : 'profile';
        return $row;
    }
}
