<?php

namespace App\Services;

/**
 * Reads the look out of an uploaded website template — a .zip (static site,
 * built React/Vue app, or a Tailwind project), an .html or a .css file — and
 * turns it into a SiteTheme: main and second colour, fonts (Google Fonts),
 * corner rounding, shadow and its @keyframes animations. Nothing in the
 * template is run or published: only its CSS (and tailwind.config colours)
 * is read, then thrown away. The site's pages and content stay as they are.
 */
class TemplateImporter
{
    private const MAX_BYTES = 25 * 1024 * 1024;   // upload
    private const MAX_CSS = 4 * 1024 * 1024;      // text read out of it

    /** @return array theme values found, plus 'palette' (colours seen, most used first) and 'found' (what was read). */
    public static function fromUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            throw new \RuntimeException('Choose a template file (.zip, .html or .css).');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('That template is over 25 MB. Upload the built site (or just its CSS).');
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        [$css, $html, $found] = match ($ext) {
            'zip' => self::readZip((string) $file['tmp_name']),
            'html', 'htm' => self::readHtml((string) file_get_contents($file['tmp_name'], false, null, 0, self::MAX_CSS)),
            'css' => [(string) file_get_contents($file['tmp_name'], false, null, 0, self::MAX_CSS), '', ['1 stylesheet']],
            default => throw new \RuntimeException('Upload a .zip, .html or .css template.'),
        };
        if (trim($css) === '' && trim($html) === '') {
            throw new \RuntimeException('No styles were found in that template.');
        }
        $theme = self::analyse($css, $html);
        $theme['found'] = $found;
        $theme['source'] = (string) ($file['name'] ?? 'template');
        return $theme;
    }

    /** CSS files, <style> blocks of HTML files and tailwind.config colours from a zip. */
    private static function readZip(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('This server cannot open .zip files. Upload the template\'s .css or .html instead.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('That .zip could not be opened.');
        }
        $css = '';
        $html = '';
        $counts = ['css' => 0, 'html' => 0, 'tailwind' => 0];
        for ($i = 0; $i < $zip->numFiles && strlen($css) < self::MAX_CSS; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_contains($name, 'node_modules/') || str_contains($name, '__MACOSX')) {
                continue;
            }
            $lower = strtolower($name);
            if (str_ends_with($lower, '.css') || str_ends_with($lower, '.scss')) {
                $css .= "\n" . $zip->getFromIndex($i, self::MAX_CSS);
                $counts['css']++;
            } elseif ((str_ends_with($lower, '.html') || str_ends_with($lower, '.htm')) && $counts['html'] < 10) {
                [$inline, $markup] = self::readHtml((string) $zip->getFromIndex($i, self::MAX_CSS));
                $css .= "\n" . $inline;
                $html .= "\n" . $markup;
                $counts['html']++;
            } elseif (preg_match('#(^|/)tailwind\.config\.(js|cjs|mjs|ts)$#', $lower)) {
                $css .= "\n/* tailwind */ " . $zip->getFromIndex($i, 200000); // its hex colours are counted like CSS
                $counts['tailwind']++;
            }
        }
        $zip->close();
        $found = array_filter([
            $counts['css'] ? $counts['css'] . ' stylesheet' . ($counts['css'] === 1 ? '' : 's') : '',
            $counts['html'] ? $counts['html'] . ' HTML page' . ($counts['html'] === 1 ? '' : 's') : '',
            $counts['tailwind'] ? 'Tailwind colours' : '',
        ]);
        if (!$found) {
            throw new \RuntimeException('No stylesheets were found in that .zip. For a React project, upload the built site (the "dist" or "build" folder) zipped.');
        }
        return [$css, $html, array_values($found)];
    }

    /** [inline CSS, the HTML (for font links)] from one HTML page. */
    private static function readHtml(string $html): array
    {
        preg_match_all('#<style[^>]*>(.*?)</style>#is', $html, $m);
        return [implode("\n", $m[1] ?? []), $html, ['1 HTML page']];
    }

    /** Works out the theme from the template's CSS. */
    public static function analyse(string $css, string $html): array
    {
        $css = preg_replace('#/\*(?!\s*tailwind).*?\*/#s', '', $css) ?? $css;

        // Colours: count every colour; the most used strong colours become the main and second colour.
        $colors = [];
        if (preg_match_all('/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $css, $m)) {
            foreach ($m[1] as $hex) {
                $hex = strlen($hex) === 3 ? $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] : $hex;
                $colors['#' . strtolower($hex)] = ($colors['#' . strtolower($hex)] ?? 0) + 1;
            }
        }
        if (preg_match_all('/rgba?\(\s*(\d{1,3})[\s,]+(\d{1,3})[\s,]+(\d{1,3})/', $css, $m, PREG_SET_ORDER)) {
            foreach ($m as $rgb) {
                $hex = sprintf('#%02x%02x%02x', min(255, (int) $rgb[1]), min(255, (int) $rgb[2]), min(255, (int) $rgb[3]));
                $colors[$hex] = ($colors[$hex] ?? 0) + 1;
            }
        }
        arsort($colors);
        $strong = array_values(array_filter(array_keys($colors), [self::class, 'isStrong']));
        $accent = $strong[0] ?? null;
        $accent2 = null;
        foreach (array_slice($strong, 1) as $candidate) {
            if (self::hueDistance($accent, $candidate) > 40) {
                $accent2 = $candidate;
                break;
            }
        }

        // Fonts: Google Fonts links/imports, else the first named font-family.
        $fontUrl = '';
        $families = [];
        if (preg_match_all('#https://fonts\.googleapis\.com/css2?\?[^"\'\)\s]+#', $html . "\n" . $css, $m)) {
            $fontUrl = html_entity_decode($m[0][0]);
            foreach ($m[0] as $url) {
                if (preg_match_all('/family=([A-Za-z0-9+]+)/', html_entity_decode($url), $f)) {
                    foreach ($f[1] as $family) {
                        $families[] = str_replace('+', ' ', $family);
                    }
                }
            }
        }
        if (preg_match_all('/font-family\s*:\s*["\']?([A-Za-z][A-Za-z0-9 \-]{1,38})["\']?\s*[,;}]/', $css, $m)) {
            foreach ($m[1] as $family) {
                if (!in_array(strtolower(trim($family)), ['inherit', 'sans-serif', 'serif', 'monospace', 'system-ui', 'initial', 'var', 'ui-sans-serif', '-apple-system'], true)) {
                    $families[] = trim($family);
                }
            }
        }
        $families = array_values(array_unique($families));
        // Which font is for headings and which for text: read from the h1–h3 and body rules when present.
        $ruleFont = static function (string $selector) use ($css): ?string {
            if (preg_match('/(?:^|[}\s,])' . $selector . '[^{]*\{[^}]*font-family\s*:\s*["\']?([A-Za-z][A-Za-z0-9 \-]{1,38})/i', $css, $m)) {
                return trim($m[1]);
            }
            return null;
        };
        $headingFont = $ruleFont('h[1-3]') ?? $families[0] ?? null;
        $bodyFont = $ruleFont('(?:body|html|:root)') ?? $families[1] ?? $families[0] ?? null;
        if ($fontUrl === '' && $headingFont) {
            // Most template fonts are on Google Fonts; ask for them there.
            $fontUrl = 'https://fonts.googleapis.com/css2?' . implode('&', array_map(
                static fn(string $f): string => 'family=' . str_replace(' ', '+', $f) . ':wght@400;600;700;800',
                array_unique(array_filter([$headingFont, $bodyFont]))
            )) . '&display=swap';
        }

        // Corner rounding and shadow: the most used values.
        $radius = null;
        if (preg_match_all('/border-radius\s*:\s*(\d{1,2})px/', $css, $m)) {
            $counts = array_count_values(array_map('intval', $m[1]));
            unset($counts[0]);
            arsort($counts);
            $radius = $counts ? (int) array_key_first($counts) : null;
        }
        $shadow = null;
        if (preg_match_all('/box-shadow\s*:\s*([^;}]{6,120})/', $css, $m)) {
            $counts = array_count_values(array_map('trim', $m[1]));
            arsort($counts);
            foreach (array_keys($counts) as $value) {
                if (preg_match('/^[0-9a-z.,()#% \-]+$/i', $value) && !str_contains($value, 'inset') && $value !== 'none') {
                    $shadow = $value;
                    break;
                }
            }
        }

        // Animations: the template's @keyframes (made safe).
        $keyframes = '';
        if (preg_match_all('/@keyframes\s+[A-Za-z0-9_-]+\s*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', $css, $m)) {
            $keyframes = self::safeKeyframes(implode("\n", array_slice(array_unique($m[0]), 0, 12)));
        }
        $firstKeyframe = preg_match('/@keyframes\s+([A-Za-z0-9_-]+)/', $keyframes, $k) ? 'kf:' . $k[1] : null;

        return array_filter([
            'accent' => $accent,
            'accent2' => $accent2,
            'heading_font' => $headingFont,
            'body_font' => $bodyFont,
            'font_url' => $fontUrl,
            'radius' => $radius,
            'shadow' => $shadow,
            'keyframes' => $keyframes,
            'animation' => $firstKeyframe ? 'fade-up' : null,
            'palette' => array_slice(array_keys($colors), 0, 16),
        ], static fn($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** Keeps only well-formed @keyframes with harmless properties; an unsafe one is dropped on its own. */
    public static function safeKeyframes(string $css): string
    {
        if ($css === '') {
            return '';
        }
        preg_match_all('/@keyframes\s+[A-Za-z0-9_-]+\s*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', $css, $m);
        $safe = array_filter($m[0], static fn(string $block): bool => !preg_match('/url\s*\(|expression\s*\(|@import|javascript:|<|>|\\\\/i', $block));
        return mb_substr(implode("\n", array_values(array_unique($safe))), 0, 20000);
    }

    /** A colour worth using as a brand colour: not near white, black or grey. */
    private static function isStrong(string $hex): bool
    {
        [$r, $g, $b] = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 510;
        $saturation = $max === $min ? 0 : ($max - $min) / (255 - abs($max + $min - 255));
        return $saturation > 0.35 && $lightness > 0.18 && $lightness < 0.75;
    }

    private static function hueDistance(?string $a, string $b): float
    {
        if (!$a) {
            return 360;
        }
        $hue = static function (string $hex): float {
            [$r, $g, $b] = [hexdec(substr($hex, 1, 2)) / 255, hexdec(substr($hex, 3, 2)) / 255, hexdec(substr($hex, 5, 2)) / 255];
            $max = max($r, $g, $b);
            $d = $max - min($r, $g, $b);
            if ($d == 0) {
                return 0;
            }
            $h = match (true) {
                $max == $r => fmod(($g - $b) / $d, 6),
                $max == $g => ($b - $r) / $d + 2,
                default => ($r - $g) / $d + 4,
            };
            return fmod($h * 60 + 360, 360);
        };
        $d = abs($hue($a) - $hue($b));
        return min($d, 360 - $d);
    }
}
