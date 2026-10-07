<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\SiteTheme;
use App\Services\TemplateImporter;

/**
 * The website's look: edited by hand, or read from an uploaded template.
 * An import is shown first ("this is what we found") and only applied when
 * Super Admin confirms; pages and content are never changed.
 */
class ThemeController extends BaseAdminController
{
    public function index(): void
    {
        $theme = SiteTheme::get();
        View::render('admin.theme.index', [
            'pageTitle' => 'Theme',
            'activeNav' => 'pages',
            'theme' => $theme,
            'animations' => SiteTheme::animations($theme),
            'imported' => $_SESSION['theme_import'] ?? null,
        ]);
    }

    public function save(): void
    {
        $this->csrf();
        $current = SiteTheme::get();
        SiteTheme::save([
            'accent' => Request::post('accent', ''),
            'accent2' => Request::post('accent2', ''),
            'heading_font' => Request::post('heading_font', ''),
            'body_font' => Request::post('body_font', ''),
            'font_url' => trim((string) Request::post('font_url', '')),
            'radius' => Request::post('radius', 14),
            'shadow' => trim((string) Request::post('shadow', '')),
            'animation' => Request::post('animation', 'none'),
            'keyframes' => $current['keyframes'],
            'source' => $current['source'],
        ]);
        flashSuccess('Theme saved. The public pages and every portal use it now.');
        redirect('/admin/theme');
    }

    /** Reads the upload and shows what was found; nothing changes until "Apply". */
    public function import(): void
    {
        $this->csrf();
        try {
            $found = TemplateImporter::fromUpload(Request::file('template') ?? []);
        } catch (\Throwable $e) {
            flashError($e->getMessage());
            redirect('/admin/theme');
        }
        $_SESSION['theme_import'] = $found;
        flashSuccess('Template read. Check what was found below, adjust anything, then apply it.');
        redirect('/admin/theme#imported');
    }

    public function apply(): void
    {
        $this->csrf();
        $found = $_SESSION['theme_import'] ?? null;
        if (!$found) {
            redirect('/admin/theme');
        }
        // The admin may have changed the picked colours / fonts on the preview.
        foreach (['accent', 'accent2', 'heading_font', 'body_font', 'animation'] as $key) {
            $value = trim((string) Request::post($key, ''));
            if ($value !== '') {
                $found[$key] = $value;
            }
        }
        SiteTheme::save($found + SiteTheme::get());
        unset($_SESSION['theme_import']);
        flashSuccess('Template style applied. Your pages and content are unchanged — only the look is new.');
        redirect('/admin/theme');
    }

    public function discard(): void
    {
        $this->csrf();
        unset($_SESSION['theme_import']);
        redirect('/admin/theme');
    }

    public function reset(): void
    {
        $this->csrf();
        SiteTheme::reset();
        flashSuccess('Theme reset to the original design.');
        redirect('/admin/theme');
    }

    private function csrf(): void
    {
        if (!csrfVerify((string) Request::post('csrf_token', ''))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/theme');
        }
    }
}
