<?php
/**
 * Global helper functions available to every controller and view.
 */

use App\Core\Url;
use App\Models\StoreSetting;

function url(string $path = '/'): string
{
    return Url::to($path);
}

function absoluteUrl(string $path = '/'): string
{
    return Url::absolute($path);
}

function asset(string $path): string
{
    return Url::asset($path);
}

/**
 * Uploaded images may be an absolute seed URL (https://images.unsplash.com/...)
 * or an uploaded path relative to public/ (assets/uploads/products/xyz.jpg).
 */
function imageUrl(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (preg_match('#^(https?://|data:)#i', $path)) {
        return $path;
    }
    return asset($path);
}

function defaultLogoSvg(string $class = 'w-4 h-4 text-white'): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="' . $class . '"><polygon points="12,2 22,9 18,21 6,21 2,9" /></svg>';
}

function storeLogoPath(): ?string
{
    try {
        return StoreSetting::get('store_logo');
    } catch (Throwable $e) {
        return null;
    }
}

function storeLogoHtml(string $imageClass, string $fallbackSvgClass = 'w-4 h-4 text-white'): string
{
    $logo = storeLogoPath();
    if ($logo) {
        return '<img src="' . e(imageUrl($logo)) . '" alt="' . e(appName()) . ' logo" class="' . e($imageClass) . '" />';
    }
    return defaultLogoSvg($fallbackSvgClass);
}

function appName(): string
{
    try {
        $stored = StoreSetting::get('platform_name');
        if ($stored) {
            return $stored;
        }
    } catch (Throwable $e) {
        // Settings table may not exist yet during first-run setup.
    }
    return (string) App\Core\Env::get('APP_NAME', 'Ujuzi Training');
}

function roleLabel(string $slug): string
{
    return match ($slug) {
        'organisation_admin' => 'Organisations providing courses',
        'trainer' => 'Tutor',
        'attachment_trainer' => 'Organisation providing Attachment',
        'branch_admin' => 'Attachment providing admin',
        'student' => 'Student',
        default => ucwords(str_replace('_', ' ', $slug)),
    };
}

function fieldTypeLabel(string $type): string
{
    return \App\Models\FormFieldTypes::label($type);
}

function formatFormAnswer(array $field, $value): string
{
    return \App\Models\FormField::formatAnswer($field, $value);
}

function userDisplayName(?array $user): string
{
    if (!$user) {
        return 'User';
    }
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return $user['email'] ?? $user['phone'] ?? 'User';
}

function e($str): string
{
    return htmlspecialchars((string) ($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfVerify(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flashSuccess(string $message): void
{
    $_SESSION['flash_success'] = $message;
}

function timeOfDayGreeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Good Morning';
    }
    if ($hour < 17) {
        return 'Good Afternoon';
    }
    return 'Good Evening';
}

/** Like flashSuccess(), but $html is trusted markup rendered as-is (not escaped) — only pass strings built entirely from your own code, never raw user input. */
function flashSuccessHtml(string $html): void
{
    $_SESSION['flash_success_html'] = $html;
}

function flashError(string $message): void
{
    $_SESSION['flash_error'] = $message;
}

/**
 * A wa.me deep link pre-filled with $message, using the Super Admin's
 * configured WhatsApp share number (Settings). Returns null if none is set.
 */
function whatsappShareUrl(string $message): ?string
{
    $number = preg_replace('/[^0-9]/', '', (string) \App\Models\StoreSetting::get('whatsapp_share_number', ''));
    if ($number === '') {
        return null;
    }
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}

/**
 * An inline stroke icon (Lucide-style, drawn in currentColor) — used in place
 * of emojis across the portals so they look and read like an app.
 */
function icon(string $name, string $class = 'h-5 w-5'): string
{
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'book' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
        'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'folder' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'target' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'graduation' => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'plus' => '<path d="M5 12h14M12 5v14"/>',
        'more' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'file' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/>',
        'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'coins' => '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18M7 6h1v4M16.71 13.88l.7.71-2.82 2.82"/>',
        'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>',
    ];
    $body = $paths[$name] ?? $paths['more'];
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round" class="' . htmlspecialchars($class, ENT_QUOTES) . '" aria-hidden="true" focusable="false">'
        . $body . '</svg>';
}
