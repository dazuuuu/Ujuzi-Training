<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * The website's look, shared by the public pages and every portal: main and
 * second colour, fonts, corner rounding, shadow and an entrance animation for
 * sections. Edited by Super Admin, or filled in from an uploaded template
 * (TemplateImporter). Only the look changes; pages and content never do.
 */
class SiteTheme
{
    public const DEFAULTS = [
        'accent' => '#006b3f',
        'accent2' => '#bb0000',
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
        'font_url' => '',
        'radius' => 14,
        'shadow' => '0 8px 24px rgba(15,23,42,.06)',
        'animation' => 'none',
        'keyframes' => '',
        'source' => '',
    ];

    /** Entrance effects built in; imported @keyframes names are added to these. */
    public const ANIMATIONS = ['none' => 'None', 'fade-up' => 'Fade up', 'fade' => 'Fade in', 'zoom' => 'Zoom in', 'slide-left' => 'Slide in from the side'];

    public static function get(): array
    {
        static $theme = null;
        if ($theme === null) {
            try {
                $saved = json_decode((string) StoreSetting::get('site_theme', '{}'), true);
            } catch (\Throwable $e) {
                $saved = [];
            }
            $theme = self::clean(is_array($saved) ? $saved + self::DEFAULTS : self::DEFAULTS);
        }
        return $theme;
    }

    public static function save(array $theme): void
    {
        StoreSetting::set('site_theme', json_encode(self::clean($theme + self::DEFAULTS), JSON_UNESCAPED_UNICODE));
    }

    public static function reset(): void
    {
        StoreSetting::set('site_theme', null);
    }

    /** Animation names an admin can pick: the built-in ones plus those imported. */
    public static function animations(array $theme): array
    {
        $out = self::ANIMATIONS;
        if (preg_match_all('/@keyframes\s+([A-Za-z0-9_-]+)/', (string) $theme['keyframes'], $m)) {
            foreach ($m[1] as $name) {
                $out['kf:' . $name] = 'From template: ' . $name;
            }
        }
        return $out;
    }

    /** Keeps every value safe to drop into CSS. */
    public static function clean(array $t): array
    {
        $color = static fn($v, $d) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? strtolower((string) $v) : $d;
        $font = static fn($v, $d) => preg_match('/^[A-Za-z0-9 \-]{2,40}$/', trim((string) $v)) ? trim((string) $v) : $d;
        $anim = (string) ($t['animation'] ?? 'none');
        return [
            'accent' => $color($t['accent'] ?? '', self::DEFAULTS['accent']),
            'accent2' => $color($t['accent2'] ?? '', self::DEFAULTS['accent2']),
            'heading_font' => $font($t['heading_font'] ?? '', self::DEFAULTS['heading_font']),
            'body_font' => $font($t['body_font'] ?? '', self::DEFAULTS['body_font']),
            'font_url' => preg_match('#^https://fonts\.googleapis\.com/css2?\?[A-Za-z0-9=:;,@&+%._\-]+$#', (string) ($t['font_url'] ?? '')) ? (string) $t['font_url'] : '',
            'radius' => max(0, min(40, (int) ($t['radius'] ?? self::DEFAULTS['radius']))),
            'shadow' => preg_match('/^[0-9a-z.,()#% \-]{0,120}$/i', (string) ($t['shadow'] ?? '')) ? (string) $t['shadow'] : self::DEFAULTS['shadow'],
            'animation' => preg_match('/^(none|fade-up|fade|zoom|slide-left|kf:[A-Za-z0-9_-]{1,60})$/', $anim) ? $anim : 'none',
            'keyframes' => TemplateImporter::safeKeyframes((string) ($t['keyframes'] ?? '')),
            'source' => mb_substr(preg_replace('/[^\w .\-()]/u', '', (string) ($t['source'] ?? '')), 0, 120),
        ];
    }

    /**
     * The <link>/<style> that applies the theme. $scope 'public' styles the
     * page-builder pages; 'portal' maps the theme onto the portals' colours.
     */
    public static function head(string $scope): string
    {
        $t = self::get();
        if ($t === self::clean(self::DEFAULTS)) {
            return ''; // nothing changed: leave the original design untouched
        }
        $out = $t['font_url'] !== '' ? '<link rel="stylesheet" href="' . htmlspecialchars($t['font_url']) . '">' : '';
        $heading = "'" . $t['heading_font'] . "', system-ui, sans-serif";
        $body = "'" . $t['body_font'] . "', system-ui, sans-serif";
        $css = $t['keyframes'];
        if ($scope === 'portal') {
            $css .= ":root{--ke-green:{$t['accent']};--ke-green-dark:color-mix(in srgb,{$t['accent']} 80%,#000);--ke-red:{$t['accent2']};}"
                . "body.srms-theme,body.srms-theme input,body.srms-theme button,body.srms-theme select,body.srms-theme textarea{font-family:$body;}"
                . "body.srms-theme h1,body.srms-theme h2,body.srms-theme h3,.font-serif-heading{font-family:$heading;}"
                . ".btn-primary,.btn-secondary,.btn-danger{border-radius:{$t['radius']}px;}";
        } else {
            $css .= ".pb{font-family:$body;}.pb h1,.pb h2,.pb h3,.pb-title{font-family:$heading;}"
                . '.pb-btn{border-radius:' . $t['radius'] . 'px;}'
                . '.pb-card,.pb-course,.pb-person,.pb-role,.pb-faq-item,.pb-stat{border-radius:' . max(4, $t['radius'] + 4) . 'px;box-shadow:' . $t['shadow'] . ';}'
                . '.pub-signup,.pub-signin{border-radius:' . $t['radius'] . 'px;}';
            $anim = $t['animation'];
            if ($anim !== 'none') {
                $builtIn = [
                    'fade-up' => '@keyframes pbIn{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}',
                    'fade' => '@keyframes pbIn{from{opacity:0}to{opacity:1}}',
                    'zoom' => '@keyframes pbIn{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:none}}',
                    'slide-left' => '@keyframes pbIn{from{opacity:0;transform:translateX(-32px)}to{opacity:1;transform:none}}',
                ];
                $name = str_starts_with($anim, 'kf:') ? substr($anim, 3) : 'pbIn';
                $css .= ($builtIn[$anim] ?? '')
                    . '@media (prefers-reduced-motion:no-preference){.pb-anim .pb-sec{opacity:0}.pb-anim .pb-sec.is-in{opacity:1;animation:' . $name . ' .7s ease both}}';
                $out .= '<script>document.addEventListener("DOMContentLoaded",function(){var r=document.querySelector(".pb");if(!r||!("IntersectionObserver" in window))return;r.classList.add("pb-anim");var o=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add("is-in");o.unobserve(e.target);}});},{threshold:.12});r.querySelectorAll(".pb-sec").forEach(function(s){o.observe(s);});});</script>';
            }
            // The page-builder accent follows the theme.
            $css .= '.pb{--pb-accent:' . $t['accent'] . ';}.pub-nav{--pub-accent:' . $t['accent'] . ';}';
        }
        return $out . '<style>' . $css . '</style>';
    }
}
