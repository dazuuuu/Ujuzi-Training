<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * The public pages as Super Admin lays them out: each page is an ordered list
 * of sections (hero, text, cards, courses …). A section has its own content
 * fields plus layout controls shared by every section (width, spacing,
 * background, alignment, text size, hidden). The navbar and footer are
 * edited the same way under the 'site' settings.
 *
 * Nothing is stored until Super Admin saves, so every page starts as the
 * design it had before (defaults()).
 */
class PageBuilder
{
    public const PAGES = [
        'home' => 'Homepage',
        'about' => 'About us',
        'courses' => 'Courses',
        'signup' => 'Sign up page',
        'login' => 'Sign in page',
    ];

    /** Every editable page: the built-in ones, then Super Admin's own pages ('c-' + slug). */
    public static function pages(): array
    {
        $pages = self::PAGES;
        foreach (self::customPages() as $slug => $meta) {
            $pages['c-' . $slug] = $meta['title'];
        }
        return $pages;
    }

    /** slug => [title, description, in_nav, published], in the order they were made. */
    public static function customPages(): array
    {
        $saved = self::stored('custom_pages');
        $out = [];
        foreach (is_array($saved) ? $saved : [] as $slug => $meta) {
            if (is_string($slug) && preg_match('/^[a-z0-9-]{1,60}$/', $slug) && is_array($meta)) {
                $out[$slug] = [
                    'title' => mb_substr((string) ($meta['title'] ?? $slug), 0, 120),
                    'description' => mb_substr((string) ($meta['description'] ?? ''), 0, 300),
                    'in_nav' => !empty($meta['in_nav']),
                    'published' => !empty($meta['published']),
                ];
            }
        }
        return $out;
    }

    public static function saveCustomPages(array $pages): void
    {
        StoreSetting::set('custom_pages', json_encode($pages, JSON_UNESCAPED_UNICODE));
    }

    /** A URL-safe slug from a title; never one of the site's own paths. */
    public static function slugFor(string $title): string
    {
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'page';
        $slug = mb_substr($slug, 0, 50);
        $taken = array_keys(self::customPages());
        $base = $slug;
        for ($i = 2; in_array($slug, $taken, true); $i++) {
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    /** The public address of a page key. */
    public static function pageUrl(string $page): string
    {
        $builtIn = ['home' => '/', 'about' => '/about', 'courses' => '/courses', 'signup' => '/account/register/choose', 'login' => '/account/login'];
        return $builtIn[$page] ?? (str_starts_with($page, 'c-') ? '/p/' . substr($page, 2) : '/');
    }

    public static function isPage(string $page): bool
    {
        return isset(self::pages()[$page]);
    }

    /** Controls every section has. */
    public const STYLE_FIELDS = [
        'bg' => ['Background', 'select', ['white' => 'White', 'light' => 'Light grey', 'mint' => 'Light green', 'green' => 'Green', 'dark' => 'Dark', 'custom' => 'Custom colour']],
        'bg_color' => ['Custom background colour', 'color'],
        'text_color' => ['Text colour (blank = automatic)', 'color'],
        'width' => ['Content width', 'select', ['narrow' => 'Narrow', 'normal' => 'Normal', 'wide' => 'Wide', 'full' => 'Full width']],
        'padding' => ['Spacing above & below', 'select', ['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large', 'xl' => 'Extra large']],
        'align' => ['Text alignment', 'select', ['left' => 'Left', 'center' => 'Centre']],
        'text_size' => ['Text size', 'select', ['sm' => 'Small', 'md' => 'Normal', 'lg' => 'Large']],
        'anchor' => ['Section link name (e.g. partners → /#partners)', 'text'],
        'hidden' => ['Hide this section', 'checkbox'],
    ];

    /**
     * type => [label, pages it may go on (null = any public page), fields].
     * A field is [label, kind, options?]; kind is text, textarea, url, image,
     * select, number, color, checkbox or items (a repeatable list: options
     * holds the sub-fields).
     */
    public static function types(): array
    {
        $button = [
            'button_label' => ['Button text', 'text'],
            'button_url' => ['Button link', 'url'],
        ];
        return [
            'hero' => ['Hero (big headline)', null, [
                'badge' => ['Small label above the headline', 'text'],
                'title' => ['Headline', 'text'],
                'title_highlight' => ['Headline, highlighted second line', 'text'],
                'text' => ['Text under the headline', 'textarea'],
                'button_label' => ['Main button text', 'text'],
                'button_url' => ['Main button link', 'url'],
                'button2_label' => ['Second button text', 'text'],
                'button2_url' => ['Second button link', 'url'],
                'image' => ['Picture', 'image'],
                'image_side' => ['Picture position', 'select', ['right' => 'Right', 'left' => 'Left', 'none' => 'No picture']],
                'min_height' => ['Minimum height (px, 0 = automatic)', 'number', [0, 900]],
            ]],
            'text' => ['Heading & text', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
            ] + $button],
            'image_text' => ['Picture & text', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
                'image' => ['Picture', 'image'],
                'image_side' => ['Picture position', 'select', ['left' => 'Left', 'right' => 'Right']],
                'image_height' => ['Picture height (px)', 'number', [120, 800]],
            ] + $button],
            'cards' => ['Cards (features, values …)', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'text' => ['Text under the heading', 'textarea'],
                'columns' => ['Cards per row', 'select', ['2' => '2', '3' => '3', '4' => '4']],
                'items' => ['Cards', 'items', [
                    'icon' => ['Icon or emoji', 'text'],
                    'title' => ['Title', 'text'],
                    'text' => ['Text', 'textarea'],
                ]],
            ]],
            'stats' => ['Numbers', null, [
                'title' => ['Heading', 'text'],
                'items' => ['Numbers', 'items', [
                    'value' => ['Number (e.g. 20K+)', 'text'],
                    'label' => ['Label', 'text'],
                ]],
            ]],
            'team' => ['Team', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'items' => ['People', 'items', [
                    'name' => ['Name', 'text'],
                    'role' => ['Role', 'text'],
                    'photo' => ['Photo', 'image'],
                ]],
            ]],
            'courses' => ['Courses', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'text' => ['Text under the heading', 'textarea'],
                'source' => ['Which courses', 'select', ['featured' => 'Ones ticked for the homepage', 'latest' => 'Newest public courses']],
                'limit' => ['How many', 'number', [1, 24]],
                'columns' => ['Courses per row', 'select', ['2' => '2', '3' => '3', '4' => '4', '5' => '5']],
                'card_size' => ['Card size', 'select', ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
                'layout' => ['On phones', 'select', ['swipe' => 'Swipe sideways', 'stack' => 'Stack in a grid']],
            ] + $button],
            'logos' => ['Partner logos', null, [
                'title' => ['Heading', 'text'],
                'logo_height' => ['Logo height (px)', 'number', [20, 120]],
            ]],
            'cta' => ['Call to action', null, [
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
                'button_label' => ['Main button text', 'text'],
                'button_url' => ['Main button link', 'url'],
                'button2_label' => ['Second button text', 'text'],
                'button2_url' => ['Second button link', 'url'],
                'note' => ['Small line under the buttons', 'text'],
                'note_link_label' => ['Link text in that line', 'text'],
                'note_link_url' => ['Link in that line', 'url'],
            ]],
            'organisations' => ['Organisations (live list)', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'text' => ['Text under the heading', 'textarea'],
                'kind' => ['Which organisations', 'select', ['courses' => 'Providing courses', 'attachment' => 'Providing attachment']],
                'columns' => ['Per row', 'select', ['2' => '2', '3' => '3', '4' => '4']],
                'limit' => ['How many', 'number', [1, 48]],
            ]],
            'live_stats' => ['Live numbers', null, [
                'title' => ['Heading', 'text'],
                'show_students' => ['Show students', 'checkbox'],
                'show_courses' => ['Show courses', 'checkbox'],
                'show_organisations' => ['Show organisations', 'checkbox'],
                'show_certificates' => ['Show certificates earned', 'checkbox'],
            ]],
            'faq' => ['Questions & answers', null, [
                'label' => ['Small label', 'text'],
                'title' => ['Heading', 'text'],
                'items' => ['Questions', 'items', [
                    'question' => ['Question', 'text'],
                    'answer' => ['Answer', 'textarea'],
                ]],
            ]],
            'video' => ['Video', null, [
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
                'youtube' => ['YouTube link', 'url'],
            ]],
            'contact' => ['Contact details', null, [
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
                'items' => ['Details', 'items', [
                    'label' => ['Label (e.g. Phone)', 'text'],
                    'value' => ['Value', 'text'],
                    'link' => ['Link (tel:, mailto:, https://…)', 'url'],
                ]],
            ]],
            'html' => ['Custom HTML', null, [
                'html' => ['HTML (scripts are removed)', 'html'],
            ]],
            'spacer' => ['Empty space', null, [
                'height' => ['Height (px)', 'number', [8, 400]],
            ]],
            'banner' => ['Courses banner with search', ['courses'], [
                'title' => ['Heading', 'text'],
                'text' => ['Text', 'textarea'],
                'placeholder' => ['Search box hint', 'text'],
            ]],
            'catalog' => ['All public courses', ['courses'], [
                'title' => ['Heading', 'text'],
                'columns' => ['Courses per row', 'select', ['2' => '2', '3' => '3', '4' => '4', '5' => '5']],
                'card_size' => ['Card size', 'select', ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large']],
            ]],
            'signup_roles' => ['Sign-up choices', ['signup'], [
                'student_title' => ['Student card title', 'text'],
                'student_text' => ['Student card text', 'textarea'],
                'provider_title' => ['Attachment organisation card title', 'text'],
                'provider_text' => ['Attachment organisation card text', 'textarea'],
                'note' => ['Note under the cards', 'textarea'],
            ]],
            'login_roles' => ['Sign-in choices', ['login'], [
                'note' => ['Note under the cards', 'textarea'],
                'show_admin_box' => ['Show the Super Admin login box', 'checkbox'],
            ]],
        ];
    }

    /** Navbar and footer. */
    public const SITE_FIELDS = [
        'nav_links' => ['Navbar links', 'items', [
            'label' => ['Text', 'text'],
            'url' => ['Link', 'url'],
        ]],
        'nav_signin_label' => ['Sign in button text', 'text'],
        'nav_signup_label' => ['Sign up button text', 'text'],
        'nav_show_signup' => ['Show the Sign up button', 'checkbox'],
        'nav_bg' => ['Navbar background', 'color'],
        'nav_text' => ['Navbar text colour', 'color'],
        'accent' => ['Main colour (buttons, highlights)', 'color'],
        'nav_sticky' => ['Keep the navbar at the top while scrolling', 'checkbox'],
        'footer_text' => ['Footer text', 'textarea'],
        'footer_bottom' => ['Footer bottom line', 'text'],
        'footer_links' => ['Footer links', 'items', [
            'label' => ['Text', 'text'],
            'url' => ['Link', 'url'],
        ]],
        'footer_bg' => ['Footer background', 'color'],
    ];

    public static function siteDefaults(): array
    {
        return [
            'nav_links' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Courses', 'url' => '/courses'],
                ['label' => 'About', 'url' => '/about'],
            ],
            'nav_signin_label' => 'Sign in',
            'nav_signup_label' => 'Sign up',
            'nav_show_signup' => true,
            'nav_bg' => '#ffffff',
            'nav_text' => '#374151',
            'accent' => '#006b3f',
            'nav_sticky' => true,
            'footer_text' => HomepageContent::get('footer_text'),
            'footer_bottom' => HomepageContent::get('footer_bottom'),
            'footer_links' => [
                ['label' => 'Courses', 'url' => '/courses'],
                ['label' => 'About', 'url' => '/about'],
                ['label' => 'Sign in', 'url' => '/account/login'],
                ['label' => 'Sign up', 'url' => '/account/register/choose'],
            ],
            'footer_bg' => '#0f172a',
        ];
    }

    /** What each page looked like before it could be edited. */
    public static function defaults(string $page): array
    {
        $h = static fn(string $key): string => HomepageContent::get($key);
        $app = appName();
        $s = static fn(string $type, array $content, array $style = []): array => ['type' => $type, 'content' => $content, 'style' => $style];

        switch ($page) {
            case 'home':
                return [
                    $s('hero', [
                        'badge' => $h('hero_badge'), 'title' => $h('hero_title_1'), 'title_highlight' => $h('hero_title_2'),
                        'text' => $h('hero_text'), 'button_label' => 'Sign up free', 'button_url' => '/account/register/choose',
                        'button2_label' => $h('hero_button_1'), 'button2_url' => '/courses',
                        'image' => (string) (StoreSetting::get('hero_image') ?? ''), 'image_side' => 'right', 'min_height' => 0,
                    ], ['bg' => 'mint', 'padding' => 'lg']),
                    $s('courses', [
                        'label' => $h('courses_label'), 'title' => $h('courses_title'), 'text' => $h('courses_text'),
                        'source' => 'featured', 'limit' => 8, 'columns' => '4', 'card_size' => 'md', 'layout' => 'swipe',
                        'button_label' => $h('courses_button'), 'button_url' => '/courses',
                    ], ['bg' => 'light', 'anchor' => 'courses']),
                    $s('logos', ['title' => $h('trusted_title'), 'logo_height' => 42], ['anchor' => 'partners', 'align' => 'center']),
                    $s('cta', [
                        'title' => $h('signup_title'), 'text' => $h('signup_text'),
                        'button_label' => 'Sign up', 'button_url' => '/account/register/choose',
                        'button2_label' => '', 'button2_url' => '', 'note' => 'Already have an account?',
                        'note_link_label' => 'Sign in', 'note_link_url' => '/account/login',
                    ], ['padding' => 'md', 'align' => 'center']),
                ];
            case 'about':
                return [
                    $s('hero', [
                        'badge' => 'Our Story', 'title' => 'Empowering Learners,', 'title_highlight' => 'Transforming Futures',
                        'text' => $app . ' is a comprehensive, role-based Learning Management System built to bridge the gap between education, professional training, and real-world attachment experiences — all in one powerful platform.',
                        'button_label' => 'Browse Courses', 'button_url' => '/courses',
                        'button2_label' => 'Join for Free', 'button2_url' => '/account/register/choose',
                        'image' => '', 'image_side' => 'none', 'min_height' => 0,
                    ], ['bg' => 'green', 'padding' => 'lg']),
                    $s('stats', ['title' => '', 'items' => [
                        ['value' => '20K+', 'label' => 'Active Students'], ['value' => '500+', 'label' => 'Expert Tutors'],
                        ['value' => '1,200+', 'label' => 'Courses'], ['value' => '95%', 'label' => 'Success Rate'],
                    ]], ['bg' => 'white', 'align' => 'center', 'padding' => 'md']),
                    $s('cards', ['label' => 'What Drives Us', 'title' => 'Our Mission, Vision & Values', 'text' => '', 'columns' => '3', 'items' => [
                        ['icon' => '🎯', 'title' => 'Our Mission', 'text' => 'To make quality, role-based education accessible to every learner, tutor, and organisation — regardless of geography or institution size.'],
                        ['icon' => '🌍', 'title' => 'Our Vision', 'text' => 'To be the leading platform connecting learners with courses, organisations with talent, and students with real-world attachment opportunities.'],
                        ['icon' => '💡', 'title' => 'Our Values', 'text' => 'Excellence, integrity, inclusivity, and innovation drive every feature we build and every learner journey we support on this platform.'],
                    ]], ['bg' => 'light', 'align' => 'center']),
                    $s('cards', ['label' => 'Platform Features', 'title' => 'What ' . $app . ' Offers', 'text' => 'A multi-role, multi-organisation platform built for real-world education and training workflows.', 'columns' => '3', 'items' => [
                        ['icon' => '📚', 'title' => 'Online Courses', 'text' => 'Organisations publish courses. Tutors teach. Students learn at their own pace and earn verified certificates.'],
                        ['icon' => '🏢', 'title' => 'Multi-Organisation', 'text' => 'Multiple organisations operate independently with their own branches, tutors, and students on the same platform.'],
                        ['icon' => '🏆', 'title' => 'Certificates', 'text' => 'Students receive shareable certificates on completion. Attachment providers issue recommendation letters.'],
                        ['icon' => '🤝', 'title' => 'Attachment Management', 'text' => 'Attachment organisations register branches and accept students after they complete relevant courses.'],
                        ['icon' => '📍', 'title' => 'Branch System', 'text' => 'Both course organisations and attachment providers manage multiple branches with dedicated branch admins.'],
                        ['icon' => '🔐', 'title' => 'Role-Based Security', 'text' => 'Every user has a specific role with a tailored dashboard and permissions — from student to super admin.'],
                    ]]),
                    $s('team', ['label' => 'Meet the Team', 'title' => 'The People Behind ' . $app, 'items' => [
                        ['name' => 'David K.', 'role' => 'Founder & CEO', 'photo' => ''],
                        ['name' => 'Mary J.', 'role' => 'Head of Curriculum', 'photo' => ''],
                        ['name' => 'James O.', 'role' => 'Lead Engineer', 'photo' => ''],
                        ['name' => 'Grace A.', 'role' => 'Head of Partnerships', 'photo' => ''],
                    ]], ['bg' => 'light', 'align' => 'center']),
                    $s('cta', [
                        'title' => 'Ready to Start Learning?',
                        'text' => 'Join thousands of learners already using ' . $app . ' to advance their careers and unlock new opportunities.',
                        'button_label' => 'Browse Courses', 'button_url' => '/courses',
                        'button2_label' => 'Register as Student', 'button2_url' => '/account/register', 'note' => '',
                    ], ['align' => 'center']),
                ];
            case 'courses':
                return [
                    $s('banner', [
                        'title' => 'Explore All Courses',
                        'text' => 'Browse our library of expert-led courses. Learn new skills and advance your career today.',
                        'placeholder' => 'What do you want to learn today?',
                    ], ['bg' => 'dark', 'align' => 'center', 'padding' => 'lg']),
                    $s('catalog', ['title' => 'Public Courses', 'columns' => '3', 'card_size' => 'md'], ['bg' => 'light', 'width' => 'wide', 'padding' => 'none']),
                ];
            case 'signup':
                return [
                    $s('text', [
                        'label' => 'Choose your role', 'title' => 'Create your account',
                        'text' => 'Sign up for the role that fits you. After you register you complete your profile and land on your dashboard.',
                        'button_label' => '', 'button_url' => '',
                    ], ['align' => 'center', 'padding' => 'sm', 'width' => 'narrow']),
                    $s('signup_roles', [
                        'student_title' => 'Student',
                        'student_text' => 'Enrol in courses, track your progress, and earn certificates.',
                        'provider_title' => 'Organisation providing attachment',
                        'provider_text' => 'Take on students for attachment or internship. Each organisation providing courses approves you before its students can see you.',
                        'note' => 'Tutors and branch admins get their sign-in details from their organisation. Organisations providing courses register through an invite link from Super Admin.',
                    ], ['padding' => 'sm', 'width' => 'narrow']),
                ];
            case 'login':
                return [
                    $s('text', [
                        'label' => 'Choose your role', 'title' => 'Sign in to your dashboard',
                        'text' => 'Each role has its own login. After you sign in you land on that role’s dashboard.',
                        'button_label' => '', 'button_url' => '',
                    ], ['align' => 'center', 'padding' => 'sm', 'width' => 'narrow']),
                    $s('login_roles', ['note' => '', 'show_admin_box' => true], ['padding' => 'sm', 'width' => 'narrow']),
                ];
        }
        if (str_starts_with($page, 'c-')) {
            $title = self::customPages()[substr($page, 2)]['title'] ?? 'New page';
            return [
                $s('hero', ['badge' => '', 'title' => $title, 'title_highlight' => '', 'text' => 'Write what this page is about.',
                    'button_label' => '', 'button_url' => '', 'button2_label' => '', 'button2_url' => '', 'image' => '', 'image_side' => 'none', 'min_height' => 0], ['bg' => 'mint', 'padding' => 'lg']),
                $s('text', ['label' => '', 'title' => 'A section heading', 'text' => 'Add, move or remove sections on the left. Click any part of this preview to edit it.', 'button_label' => '', 'button_url' => ''], []),
            ];
        }
        return [];
    }

    /** The page's sections: the Super Admin's live preview draft, else what was saved, else the default design. */
    public static function sections(string $page): array
    {
        $draft = self::previewDraft();
        if ($draft !== null && isset($draft['pages'][$page])) {
            return self::normalizeSections($page, $draft['pages'][$page]);
        }
        $saved = self::stored('page_layout_' . $page);
        return self::normalizeSections($page, is_array($saved) ? $saved : self::defaults($page));
    }

    public static function site(): array
    {
        $draft = self::previewDraft();
        $saved = $draft['site'] ?? self::stored('page_site');
        return self::normalizeSite(is_array($saved) ? $saved : []);
    }

    public static function save(string $page, array $sections): void
    {
        StoreSetting::set('page_layout_' . $page, json_encode(self::normalizeSections($page, $sections), JSON_UNESCAPED_UNICODE));
    }

    public static function saveSite(array $site): void
    {
        StoreSetting::set('page_site', json_encode(self::normalizeSite($site), JSON_UNESCAPED_UNICODE));
    }

    public static function reset(string $page): void
    {
        StoreSetting::set('page_layout_' . $page, '');
    }

    /**
     * Super Admin's unsaved changes, shown in the editor's preview frame. The
     * frame passes ?pb_preview=<token>, a random token the editor was given;
     * visitors never have one, so they always see the published page. (A
     * token rather than the admin session, because the sign-up and sign-in
     * pages run under the portal's session.)
     */
    public static function previewToken(): ?string
    {
        $token = (string) ($_GET['pb_preview'] ?? '');
        return preg_match('/^[a-f0-9]{32}$/', $token) ? $token : null;
    }

    private static function previewDraft(): ?array
    {
        static $draft = false;
        if ($draft === false) {
            $token = self::previewToken();
            $draft = $token ? self::stored('page_builder_draft_' . $token) : null;
        }
        return is_array($draft) ? $draft : null;
    }

    public static function setPreviewDraft(string $token, string $page, array $sections, array $site): void
    {
        StoreSetting::set('page_builder_draft_' . $token, json_encode(['pages' => [$page => $sections], 'site' => $site], JSON_UNESCAPED_UNICODE));
    }

    private static function stored(string $key)
    {
        try {
            $raw = (string) StoreSetting::get($key, '');
        } catch (\Throwable $e) {
            return null;
        }
        return $raw !== '' ? json_decode($raw, true) : null;
    }

    /** Keeps only known section types (allowed on this page) and known fields, with sane values. */
    public static function normalizeSections(string $page, array $sections): array
    {
        $types = self::types();
        $out = [];
        foreach (array_slice($sections, 0, 60) as $section) {
            $type = (string) ($section['type'] ?? '');
            if (!isset($types[$type])) {
                continue;
            }
            [, $pages, $fields] = $types[$type];
            if ($pages !== null && !in_array($page, $pages, true)) {
                continue;
            }
            $out[] = [
                'type' => $type,
                'content' => self::cleanFields($fields, is_array($section['content'] ?? null) ? $section['content'] : []),
                'style' => self::cleanFields(self::STYLE_FIELDS, is_array($section['style'] ?? null) ? $section['style'] : []),
            ];
        }
        return $out;
    }

    public static function normalizeSite(array $site): array
    {
        return self::cleanFields(self::SITE_FIELDS, $site + self::siteDefaults());
    }

    private static function cleanFields(array $fields, array $values): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            $kind = $field[1];
            $value = $values[$key] ?? null;
            switch ($kind) {
                case 'checkbox':
                    $out[$key] = !empty($value) && $value !== 'false';
                    break;
                case 'number':
                    [$min, $max] = $field[2];
                    $out[$key] = $value === null || $value === '' ? null : max($min, min($max, (int) $value));
                    break;
                case 'select':
                    $out[$key] = isset($field[2][(string) $value]) ? (string) $value : null;
                    break;
                case 'color':
                    $out[$key] = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value) ? (string) $value : '';
                    break;
                case 'url':
                    $url = trim((string) $value);
                    // Site paths, web links, mail and phone only — never javascript: and the like.
                    $out[$key] = preg_match('#^(/|https?://|mailto:|tel:|\#)#i', $url) ? mb_substr($url, 0, 500) : '';
                    break;
                case 'items':
                    $items = [];
                    foreach (array_slice(is_array($value) ? $value : [], 0, 40) as $item) {
                        if (is_array($item)) {
                            $items[] = self::cleanFields($field[2], $item);
                        }
                    }
                    $out[$key] = $items;
                    break;
                case 'html':
                    $out[$key] = self::safeHtml((string) $value);
                    break;
                case 'image':
                    $path = trim((string) $value);
                    $out[$key] = preg_match('#^[A-Za-z0-9_./-]+$#', $path) && !str_contains($path, '..') ? $path : '';
                    break;
                default:
                    $out[$key] = mb_substr(trim(str_replace("\r\n", "\n", (string) $value)), 0, $kind === 'textarea' ? 3000 : 300);
            }
        }
        return $out;
    }

    /**
     * Super Admin's own HTML, made safe to show: no scripts, frames, forms or
     * event handlers, and no javascript: links. Layout and styling stay.
     */
    public static function safeHtml(string $html): string
    {
        $html = mb_substr($html, 0, 20000);
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta|base)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|iframe|object|embed|form|input|link|meta|base)\b[^>]*>#i', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data):[^"\'>\s]*/i', '$1=$2#', $html);
        return trim((string) $html);
    }

    /** A site link as an href: site paths go through url(). */
    public static function href(string $link): string
    {
        return str_starts_with($link, '/') ? url($link) : $link;
    }

    /** Everything the editor needs to draw its forms. */
    public static function schema(): array
    {
        $types = [];
        foreach (self::types() as $type => [$label, $pages, $fields]) {
            $types[$type] = ['label' => $label, 'pages' => $pages, 'fields' => self::fieldSchema($fields)];
        }
        return [
            'types' => $types,
            'style' => self::fieldSchema(self::STYLE_FIELDS),
            'site' => self::fieldSchema(self::SITE_FIELDS),
        ];
    }

    private static function fieldSchema(array $fields): array
    {
        $out = [];
        foreach ($fields as $key => $field) {
            $entry = ['key' => $key, 'label' => $field[0], 'kind' => $field[1]];
            if ($field[1] === 'select') {
                $entry['options'] = $field[2];
            } elseif ($field[1] === 'number') {
                $entry['min'] = $field[2][0];
                $entry['max'] = $field[2][1];
            } elseif ($field[1] === 'items') {
                $entry['fields'] = self::fieldSchema($field[2]);
            }
            $out[] = $entry;
        }
        return $out;
    }
}
