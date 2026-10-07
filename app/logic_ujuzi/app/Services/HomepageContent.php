<?php

namespace App\Services;

use App\Core\Database;
use App\Models\StoreSetting;

/**
 * The words on the public homepage, which Super Admin can change without
 * touching its layout, plus the "Trusted by" logos and the featured courses.
 * Every text starts as what the page said before, so nothing changes until
 * someone edits it.
 */
class HomepageContent
{
    /** section => [key => [label, default, 'text'|'textarea']] */
    public const FIELDS = [
        'Hero' => [
            'hero_badge' => ['Small label above the headline', 'Platform for Online Learning', 'text'],
            'hero_title_1' => ['Headline, first line', 'Learn New Skills.', 'text'],
            'hero_title_2' => ['Headline, second line (highlighted)', 'Advance Your Future.', 'text'],
            'hero_text' => ['Text under the headline', 'Courses from real organisations, certificates you can verify, and attachment placements when you finish.', 'textarea'],
            'hero_button_1' => ['Courses button', 'Explore courses', 'text'],
        ],
        'Courses' => [
            'courses_label' => ['Small label', 'Popular Courses', 'text'],
            'courses_title' => ['Heading', 'Start learning with our most popular courses', 'text'],
            'courses_text' => ['Text under the heading', 'Hand-picked courses from organisations on the platform.', 'textarea'],
            'courses_button' => ['Button under the courses', 'View all courses', 'text'],
        ],
        'Partners' => [
            'trusted_title' => ['Heading', 'Our partners', 'text'],
        ],
        'Sign up' => [
            'signup_title' => ['Heading', 'Ready to start?', 'text'],
            'signup_text' => ['Text under the heading', 'Create a free account as a student, or as an organisation offering attachment.', 'textarea'],
        ],
        'Footer' => [
            'footer_text' => ['Text in the footer', 'Empowering learners with quality education and practical skills for a better future.', 'textarea'],
            'footer_bottom' => ['Bottom line', 'Made for education.', 'text'],
        ],
    ];

    /** Shown until Super Admin uploads real logos (the page's original placeholder names). */
    public const DEFAULT_LOGO_NAMES = ['GOOGLE', 'MICROSOFT', 'AMAZON', 'DELOITTE', 'IBM', 'AIRBNB'];

    private static ?array $saved = null;

    public static function get(string $key): string
    {
        if (self::$saved === null) {
            try {
                self::$saved = json_decode((string) StoreSetting::get('homepage_content', '{}'), true) ?: [];
            } catch (\Throwable $e) {
                self::$saved = [];
            }
        }
        $value = self::$saved[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? $value : self::defaultFor($key);
    }

    public static function defaultFor(string $key): string
    {
        foreach (self::FIELDS as $fields) {
            if (isset($fields[$key])) {
                return $fields[$key][1];
            }
        }
        return '';
    }

    /** Saves only known keys; a blank or unchanged field goes back to its original text. */
    public static function save(array $input): void
    {
        $out = [];
        foreach (self::FIELDS as $fields) {
            foreach ($fields as $key => [$label, $default]) {
                $value = trim(str_replace("\r\n", "\n", (string) ($input[$key] ?? '')));
                if ($value !== '' && $value !== $default) {
                    $out[$key] = mb_substr($value, 0, 600);
                }
            }
        }
        StoreSetting::set('homepage_content', json_encode($out, JSON_UNESCAPED_UNICODE));
        self::$saved = $out;
    }

    /** @return array<int, array{name: string, image: string}> */
    public static function logos(): array
    {
        try {
            $logos = json_decode((string) StoreSetting::get('homepage_logos', '[]'), true);
        } catch (\Throwable $e) {
            $logos = [];
        }
        return is_array($logos) ? array_values(array_filter($logos, static fn($l) => is_array($l) && !empty($l['image']))) : [];
    }

    public static function saveLogos(array $logos): void
    {
        StoreSetting::set('homepage_logos', json_encode(array_values($logos)));
    }

    /** Published courses Super Admin ticked for the homepage, newest first. */
    public static function featuredCourses(int $limit = 8): array
    {
        try {
            return Database::connection()->query(
                "SELECT c.id, c.title, c.description, c.cover_image, c.enrollment_fee_ksh, c.visibility, c.created_at,
                        o.name AS organisation_name, cat.name AS category_name
                 FROM courses c
                 INNER JOIN organisations o ON o.id = c.organisation_id
                 LEFT JOIN organisation_categories cat ON cat.id = c.category_id
                 WHERE c.featured_on_home = 1 AND c.is_published = 1" . \App\Models\Course::approvedSql() . " AND o.is_active = 1
                 ORDER BY COALESCE(c.updated_at, c.created_at) DESC
                 LIMIT " . (int) $limit
            )->fetchAll();
        } catch (\Throwable $e) {
            return []; // before the migration adding featured_on_home runs
        }
    }

    /** Every published course, for Super Admin to tick which appear on the homepage. */
    public static function publishedCourses(): array
    {
        return Database::connection()->query(
            "SELECT c.id, c.title, c.cover_image, c.visibility, c.enrollment_fee_ksh, c.featured_on_home,
                    o.name AS organisation_name, cat.name AS category_name
             FROM courses c
             INNER JOIN organisations o ON o.id = c.organisation_id
             LEFT JOIN organisation_categories cat ON cat.id = c.category_id
             WHERE c.is_published = 1
             ORDER BY c.featured_on_home DESC, o.name ASC, c.title ASC"
        )->fetchAll();
    }

    /** Makes exactly these published courses the homepage's featured ones. */
    public static function setFeatured(array $courseIds): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        $pdo = Database::connection();
        $pdo->exec('UPDATE courses SET featured_on_home = 0');
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE courses SET featured_on_home = 1 WHERE is_published = 1 AND id IN ($placeholders)");
            $stmt->execute($ids);
            return $stmt->rowCount();
        }
        return 0;
    }
}
