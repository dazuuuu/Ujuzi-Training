<?php

namespace App\Models;

use App\Core\Database;

class OrganisationBranch
{
    public static function forOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM organisation_branches WHERE organisation_id = ? ORDER BY sort_order ASC, title ASC'
        );
        $stmt->execute([$organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM organisation_branches WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO organisation_branches (organisation_id, title, location, details, cover_image, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['organisation_id'],
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
        return $row;
    }

    private static function encodeDetails($details): ?string
    {
        $clean = self::extras(['details' => $details]);
        return $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
    }
}
