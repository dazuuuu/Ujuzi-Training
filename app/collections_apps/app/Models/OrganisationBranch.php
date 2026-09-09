<?php

namespace App\Models;

use App\Core\Database;

class OrganisationBranch
{
    public static function forOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM organisation_branches
             WHERE organisation_id = ? AND COALESCE(owner_type, 'organisation') = 'organisation'
             ORDER BY sort_order ASC, title ASC"
        );
        $stmt->execute([$organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forAttachmentProvider(int $providerUserId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM organisation_branches
             WHERE owner_type = 'attachment_provider' AND owner_user_id = ?
             ORDER BY sort_order ASC, title ASC"
        );
        $stmt->execute([$providerUserId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM organisation_branches
             WHERE owner_type = 'user' AND owner_user_id = ?
             ORDER BY sort_order ASC, title ASC"
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organisation_branches WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findWithOrganisation(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT b.*, o.name AS organisation_name, o.is_active AS organisation_is_active,
                    u.first_name AS owner_first_name, u.last_name AS owner_last_name, u.email AS owner_email
             FROM organisation_branches b
             LEFT JOIN organisations o ON o.id = b.organisation_id
             LEFT JOIN users u ON u.id = b.owner_user_id
             WHERE b.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function groupedForActiveOrganisations(): array
    {
        $stmt = Database::connection()->query(
            "SELECT b.*, o.name AS organisation_name
             FROM organisation_branches b
             INNER JOIN organisations o ON o.id = b.organisation_id
             WHERE o.is_active = 1
               AND COALESCE(b.owner_type, 'organisation') = 'organisation'
             ORDER BY o.name ASC, b.sort_order ASC, b.title ASC"
        );
        $groups = [];
        foreach ($stmt->fetchAll() as $row) {
            $branch = self::hydrate($row);
            $groups[(string) $row['organisation_name']][] = $branch;
        }
        return $groups;
    }

    public static function forActiveOrganisationIds(array $organisationIds): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds), fn(int $id): bool => $id > 0)));
        if (!$organisationIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT b.*, o.name AS organisation_name
             FROM organisation_branches b
             INNER JOIN organisations o ON o.id = b.organisation_id
             WHERE o.is_active = 1
               AND COALESCE(b.owner_type, 'organisation') = 'organisation'
               AND b.organisation_id IN ($placeholders)
             ORDER BY o.name ASC, b.sort_order ASC, b.title ASC"
        );
        $stmt->execute($organisationIds);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forActiveAttachmentProviderIds(array $providerUserIds): array
    {
        $providerUserIds = array_values(array_unique(array_filter(array_map('intval', $providerUserIds), fn(int $id): bool => $id > 0)));
        if (!$providerUserIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($providerUserIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT b.*, o.name AS organisation_name,
                    u.first_name AS owner_first_name, u.last_name AS owner_last_name, u.email AS owner_email
             FROM organisation_branches b
             INNER JOIN users u ON u.id = b.owner_user_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = b.organisation_id
             WHERE b.owner_type = 'attachment_provider'
               AND u.is_active = 1
               AND r.slug = 'attachment_trainer'
               AND b.owner_user_id IN ($placeholders)
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC, b.sort_order ASC, b.title ASC"
        );
        $stmt->execute($providerUserIds);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO organisation_branches (organisation_id, owner_type, owner_user_id, title, location, details, cover_image, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $fields['owner_type'] ?? 'organisation',
            !empty($fields['owner_user_id']) ? (int) $fields['owner_user_id'] : null,
            $fields['title'],
            $fields['location'],
            self::encodeDetails($fields['details'] ?? []),
            $fields['cover_image'] ?? null,
            (int) ($fields['sort_order'] ?? 0),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE organisation_branches SET title = ?, location = ?, details = ?, cover_image = ?, sort_order = ? WHERE id = ?'
        )->execute([
            $fields['title'],
            $fields['location'],
            self::encodeDetails($fields['details'] ?? []),
            $fields['cover_image'] ?? null,
            (int) ($fields['sort_order'] ?? 0),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM organisation_branches WHERE id = ?')->execute([$id]);
    }

    public static function extras(array $branch): array
    {
        $details = $branch['details'] ?? [];
        if (is_string($details) && $details !== '') {
            $details = json_decode($details, true) ?: [];
        }
        if (!is_array($details)) {
            return [];
        }
        $out = [];
        foreach ($details as $label => $value) {
            $label = trim((string) $label);
            $value = trim((string) $value);
            if ($label === '' || $value === '') {
                continue;
            }
            $out[$label] = $value;
        }
        return $out;
    }

    public static function asFormRows(int $organisationId): array
    {
        $rows = [];
        foreach (self::forOrganisation($organisationId) as $branch) {
            $rows[] = [
                'id' => (int) $branch['id'],
                'name' => (string) ($branch['title'] ?? ''),
                'location' => (string) ($branch['location'] ?? ''),
                'extra' => self::extras($branch),
            ];
        }
        return $rows;
    }

    /**
     * Replace organisation branches from a profile form answer list.
     * Existing cover images are kept when a submitted row still has that branch id.
     */
    public static function syncFromFormRows(int $organisationId, array $rows): void
    {
        $existing = self::forOrganisation($organisationId);
        $byId = [];
        foreach ($existing as $branch) {
            $byId[(int) $branch['id']] = $branch;
        }

        $keepIds = [];
        $order = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['name'] ?? $row['title'] ?? ''));
            $location = trim((string) ($row['location'] ?? ''));
            if ($title === '' || $location === '') {
                continue;
            }
            $details = $row['extra'] ?? $row['extras'] ?? $row['details'] ?? [];
            if (!is_array($details)) {
                $details = [];
            }
            $id = (int) ($row['id'] ?? 0);
            $cover = null;
            $sort = $order;
            if ($id > 0 && isset($byId[$id])) {
                $cover = $byId[$id]['cover_image'] ?? null;
                $sort = (int) ($byId[$id]['sort_order'] ?? $order);
                self::update($id, [
                    'title' => $title,
                    'location' => $location,
                    'details' => $details,
                    'cover_image' => $cover,
                    'sort_order' => $sort,
                ]);
                $keepIds[] = $id;
            } else {
                $keepIds[] = self::create([
                    'organisation_id' => $organisationId,
                    'owner_type' => 'organisation',
                    'owner_user_id' => null,
                    'title' => $title,
                    'location' => $location,
                    'details' => $details,
                    'cover_image' => null,
                    'sort_order' => $order,
                ]);
            }
            $order++;
        }

        foreach ($existing as $branch) {
            $id = (int) $branch['id'];
            if (!in_array($id, $keepIds, true)) {
                self::delete($id);
            }
        }
    }

    public static function asProviderFormRows(int $providerUserId): array
    {
        $rows = [];
        foreach (self::forAttachmentProvider($providerUserId) as $branch) {
            $rows[] = [
                'id' => (int) $branch['id'],
                'name' => (string) ($branch['title'] ?? ''),
                'location' => (string) ($branch['location'] ?? ''),
                'extra' => self::extras($branch),
            ];
        }
        return $rows;
    }

    public static function asUserFormRows(int $userId): array
    {
        $rows = [];
        foreach (self::forUser($userId) as $branch) {
            $rows[] = ['id' => (int) $branch['id'], 'name' => (string) ($branch['title'] ?? ''), 'location' => (string) ($branch['location'] ?? ''), 'extra' => self::extras($branch)];
        }
        return $rows;
    }

    public static function syncUserFormRows(int $userId, array $rows): void
    {
        $existing = self::forUser($userId);
        $byId = [];
        foreach ($existing as $branch) $byId[(int) $branch['id']] = $branch;
        $keepIds = [];
        foreach ($rows as $order => $row) {
            if (!is_array($row)) continue;
            $title = trim((string) ($row['name'] ?? $row['title'] ?? ''));
            $location = trim((string) ($row['location'] ?? ''));
            if ($title === '' || $location === '') continue;
            $details = $row['extra'] ?? $row['extras'] ?? $row['details'] ?? [];
            $details = is_array($details) ? $details : [];
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && isset($byId[$id])) {
                self::update($id, ['title' => $title, 'location' => $location, 'details' => $details, 'cover_image' => $byId[$id]['cover_image'] ?? null, 'sort_order' => (int) ($byId[$id]['sort_order'] ?? $order)]);
                $keepIds[] = $id;
            } else {
                $keepIds[] = self::create(['organisation_id' => null, 'owner_type' => 'user', 'owner_user_id' => $userId, 'title' => $title, 'location' => $location, 'details' => $details, 'cover_image' => null, 'sort_order' => $order]);
            }
        }
        foreach ($existing as $branch) if (!in_array((int) $branch['id'], $keepIds, true)) self::delete((int) $branch['id']);
    }

    public static function syncProviderFormRows(int $providerUserId, array $rows): void
    {
        $existing = self::forAttachmentProvider($providerUserId);
        $byId = [];
        foreach ($existing as $branch) {
            $byId[(int) $branch['id']] = $branch;
        }

        $keepIds = [];
        $order = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['name'] ?? $row['title'] ?? ''));
            $location = trim((string) ($row['location'] ?? ''));
            if ($title === '' || $location === '') {
                continue;
            }
            $details = $row['extra'] ?? $row['extras'] ?? $row['details'] ?? [];
            if (!is_array($details)) {
                $details = [];
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && isset($byId[$id])) {
                self::update($id, [
                    'title' => $title,
                    'location' => $location,
                    'details' => $details,
                    'cover_image' => $byId[$id]['cover_image'] ?? null,
                    'sort_order' => (int) ($byId[$id]['sort_order'] ?? $order),
                ]);
                $keepIds[] = $id;
            } else {
                $keepIds[] = self::create([
                    'organisation_id' => null,
                    'owner_type' => 'attachment_provider',
                    'owner_user_id' => $providerUserId,
                    'title' => $title,
                    'location' => $location,
                    'details' => $details,
                    'cover_image' => null,
                    'sort_order' => $order,
                ]);
            }
            $order++;
        }

        foreach ($existing as $branch) {
            $id = (int) $branch['id'];
            if (!in_array($id, $keepIds, true)) {
                self::delete($id);
            }
        }
    }

    public static function hydrate(array $row): array
    {
        $row['details'] = self::extras($row);
        $row['owner_type'] = $row['owner_type'] ?? 'organisation';
        $ownerName = trim((string) ($row['owner_first_name'] ?? '') . ' ' . (string) ($row['owner_last_name'] ?? ''));
        if ($ownerName === '') {
            $ownerName = (string) ($row['owner_email'] ?? '');
        }
        $row['owner_name'] = $ownerName;
        return $row;
    }

    private static function encodeDetails($details): ?string
    {
        $clean = self::extras(['details' => $details]);
        return $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
    }
}
