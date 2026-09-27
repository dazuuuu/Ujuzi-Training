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
            'hero_text' => ['Text under the headline', 'Access thousands of courses taught by industry experts. Learn at your pace. Anytime, anywhere. Earn certificates and launch your career.', 'textarea'],
            'hero_button_1' => ['Main button', 'Explore Courses', 'text'],
            'hero_button_2' => ['Second button', 'How It Works', 'text'],
            'hero_trust_prefix' => ['Trust line — before the number', 'Trusted by', 'text'],
            'hero_trust_count' => ['Trust line — number (bold)', '20K+', 'text'],
            'hero_trust_suffix' => ['Trust line — after the number', 'learners worldwide', 'text'],
            'hero_card_tagline' => ['Picture card tagline (shown when no hero photo)', 'Your Learning Journey Starts Here', 'text'],
            'float_1_value' => ['Floating badge 1 — value', '20K+', 'text'],
            'float_1_label' => ['Floating badge 1 — label', 'Active Students', 'text'],
            'float_2_value' => ['Floating badge 2 — value', '95%', 'text'],
            'float_2_label' => ['Floating badge 2 — label', 'Success Rate', 'text'],
            'float_3_value' => ['Floating badge 3 — value', 'New course!', 'text'],
            'float_3_label' => ['Floating badge 3 — label', 'Just published', 'text'],
        ],
        'Popular courses' => [
            'courses_label' => ['Small label', 'Popular Courses', 'text'],
            'courses_title' => ['Heading', 'Start learning with our most popular courses', 'text'],
            'courses_text' => ['Text under the heading', 'Hand-picked courses from organisations on the platform.', 'textarea'],
            'courses_button' => ['Button under the courses', 'View all courses', 'text'],
        ],
        'Learn on your terms' => [
            'device_badge' => ['Picture card label', 'Learn on any device', 'text'],
            'device_title' => ['Picture card heading', 'Learn on Your Terms', 'text'],
            'device_text' => ['Picture card text', "Whether you're on your laptop, tablet, or phone, your learning journey goes wherever you go.", 'textarea'],
            'features_label' => ['Small label', 'Learn Anywhere, Anytime', 'text'],
            'features_title' => ['Heading', 'Learn on Your Terms', 'text'],
            'features_text' => ['Text under the heading', "Whether you're on your laptop, tablet, or phone, your learning journey goes wherever you go. Download lectures and learn offline.", 'textarea'],
            'feature_1_title' => ['Point 1 — title', 'Access on All Devices', 'text'],
            'feature_1_text' => ['Point 1 — text', 'Study on mobile, tablet, or desktop. Your progress syncs across all devices automatically.', 'textarea'],
            'feature_2_title' => ['Point 2 — title', 'Download Lectures', 'text'],
            'feature_2_text' => ['Point 2 — text', 'Download course content and continue learning even without an internet connection.', 'textarea'],
            'feature_3_title' => ['Point 3 — title', 'Earn Certificates', 'text'],
            'feature_3_text' => ['Point 3 — text', 'Complete courses to receive verified certificates you can share on LinkedIn and resumes.', 'textarea'],
            'features_button' => ['Button', 'Start Learning Today →', 'text'],
        ],
        'Trusted by' => [
            'trusted_title' => ['Heading', 'Trusted by leading companies and institutions', 'text'],
        ],
        'Footer' => [
            'footer_text' => ['Text under the logo', 'Empowering learners worldwide with quality education and practical skills for a better future.', 'textarea'],
            'footer_bottom' => ['Bottom-right line', 'Made with ❤️ for Education', 'text'],
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
                 WHERE c.featured_on_home = 1 AND c.is_published = 1 AND o.is_active = 1
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
