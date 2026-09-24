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
