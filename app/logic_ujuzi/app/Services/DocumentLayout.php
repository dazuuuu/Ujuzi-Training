<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * Where the student's details sit on an uploaded certificate or
 * recommendation-letter design. Super Admin drags each field onto the design;
 * positions are stored as percentages of the page so they land in the same
 * place on any screen and when printed. An organisation providing courses
 * can keep its own positions for its own certificate design ($organisationId);
 * without them the platform-wide positions apply.
 */
class DocumentLayout
{
    public const TYPES = ['certificate', 'recommendation_letter'];

    /** type => field => [label, sample text] */
    public const FIELDS = [
        'certificate' => [
            'name' => ['Student name', 'Grace Wanjiku Mwangi'],
            'course' => ['Course / skills done', 'Advanced Plumbing'],
            'organisation' => ['Organisation (course done under)', 'Ujuzi Tech Institute'],
            'registration_number' => ['Registration number', 'UJ0012709/26'],
            'date' => ['Date issued', '27 September 2026'],
        ],
        'recommendation_letter' => [
            'name' => ['Student name', 'Grace Wanjiku Mwangi'],
            'course' => ['Course done', 'Advanced Plumbing'],
            'organisation' => ['Organisation (attached at)', 'Fundi Attachments Ltd'],
            'branch' => ['Branch', 'Industrial Area Depot'],
            'registration_number' => ['Registration number', 'UJ0012709/26'],
            'date' => ['Date issued', '27 September 2026'],
        ],
    ];

    /** A field: x/y = centre point in % of the page; size = % of page width. */
    private const DEFAULTS = [
        'certificate' => [
            'name' => ['x' => 50, 'y' => 42, 'size' => 4.2, 'bold' => true, 'show' => true],
            'course' => ['x' => 50, 'y' => 55, 'size' => 2.4, 'bold' => true, 'show' => true],
            'organisation' => ['x' => 50, 'y' => 62, 'size' => 1.8, 'bold' => false, 'show' => true],
            'registration_number' => ['x' => 50, 'y' => 68, 'size' => 1.4, 'bold' => false, 'show' => true],
            'date' => ['x' => 50, 'y' => 80, 'size' => 1.4, 'bold' => false, 'show' => true],
        ],
        'recommendation_letter' => [
            'name' => ['x' => 50, 'y' => 30, 'size' => 3.2, 'bold' => true, 'show' => true],
            'course' => ['x' => 50, 'y' => 40, 'size' => 2.2, 'bold' => true, 'show' => true],
            'organisation' => ['x' => 50, 'y' => 48, 'size' => 1.9, 'bold' => false, 'show' => true],
            'branch' => ['x' => 50, 'y' => 54, 'size' => 1.6, 'bold' => false, 'show' => true],
            'registration_number' => ['x' => 50, 'y' => 60, 'size' => 1.4, 'bold' => false, 'show' => true],
            'date' => ['x' => 50, 'y' => 85, 'size' => 1.4, 'bold' => false, 'show' => true],
        ],
    ];

    private static function key(string $type, ?int $organisationId): string
    {
        return $type . '_layout' . ($organisationId ? '_org_' . $organisationId : '');
    }

    public static function get(string $type, ?int $organisationId = null): array
    {
        $raw = $organisationId ? StoreSetting::get(self::key($type, $organisationId)) : null;
        if ($raw === null || $raw === '') {
            $raw = StoreSetting::get(self::key($type, null), '{}');
        }
        $saved = json_decode((string) $raw, true);
        $saved = is_array($saved) ? $saved : [];
        $out = [];
        foreach (self::DEFAULTS[$type] ?? [] as $field => $default) {
            $row = is_array($saved[$field] ?? null) ? $saved[$field] : [];
            $out[$field] = [
                'x' => self::clamp($row['x'] ?? $default['x'], 0, 100),
                'y' => self::clamp($row['y'] ?? $default['y'], 0, 100),
                'size' => self::clamp($row['size'] ?? $default['size'], 0.6, 12),
                'bold' => (bool) ($row['bold'] ?? $default['bold']),
                'show' => (bool) ($row['show'] ?? $default['show']),
                'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($row['color'] ?? '')) ? $row['color'] : '#111111',
            ];
        }
        return $out;
    }

    public static function save(string $type, array $layout, ?int $organisationId = null): void
    {
        $clean = [];
        foreach (array_keys(self::DEFAULTS[$type] ?? []) as $field) {
            $row = is_array($layout[$field] ?? null) ? $layout[$field] : [];
            $clean[$field] = [
                'x' => round(self::clamp($row['x'] ?? 50, 0, 100), 2),
                'y' => round(self::clamp($row['y'] ?? 50, 0, 100), 2),
                'size' => round(self::clamp($row['size'] ?? 2, 0.6, 12), 2),
                'bold' => !empty($row['bold']),
                'show' => !empty($row['show']),
                'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($row['color'] ?? '')) ? $row['color'] : '#111111',
            ];
        }
        StoreSetting::set(self::key($type, $organisationId), json_encode($clean));
    }

    public static function reset(string $type, ?int $organisationId = null): void
    {
        StoreSetting::set(self::key($type, $organisationId), null);
    }

    private static function clamp($value, float $min, float $max): float
    {
        return max($min, min($max, (float) $value));
    }
}
