<?php

namespace App\Controllers\Admin;

use App\Core\AccountNav;
use App\Core\Request;
use App\Core\View;
use App\Services\PageBuilder;

/**
 * Super Admin → Navigation: rename and reorder the menu of every portal
 * (students, tutors, both kinds of organisation and branch admin), Super
 * Admin's own menu, and the public website's top menu.
 */
class NavigationController extends BaseAdminController
{
    public const PUBLIC_PORTAL = 'public';

    public function index(): void
    {
        $portals = [];
        foreach (AccountNav::portals() as $key => $label) {
            $portals[$key] = [
                'label' => $label,
                'items' => AccountNav::orderedItems($key),
                'defaults' => AccountNav::defaultLabels($key),
            ];
        }
        $tab = (string) Request::query('portal', AccountNav::PORTAL_ADMIN);
        if ($tab !== self::PUBLIC_PORTAL && !isset($portals[$tab])) {
            $tab = AccountNav::PORTAL_ADMIN;
        }

        View::render('admin.navigation.index', [
            'pageTitle' => 'Navigation',
            'activeNav' => 'navigation',
            'portals' => $portals,
            'publicLinks' => PageBuilder::site()['nav_links'] ?? [],
            'tab' => $tab,
        ]);
    }

    public function update(string $portal): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/navigation?portal=' . rawurlencode($portal));
        }

        if ($portal === self::PUBLIC_PORTAL) {
            $labels = (array) Request::post('link_label', []);
            $urls = (array) Request::post('link_url', []);
            $links = [];
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $href = trim((string) ($urls[$i] ?? ''));
                if ($label !== '' && $href !== '') {
                    $links[] = ['label' => mb_substr($label, 0, 60), 'url' => mb_substr($href, 0, 500)];
                }
            }
            $site = PageBuilder::site();
            $site['nav_links'] = $links;
            PageBuilder::saveSite($site);
            flashSuccess('Public website menu saved.');
            redirect('/admin/navigation?portal=public');
        }

        if (!array_key_exists($portal, AccountNav::portals())) {
            flashError('Unknown portal.');
            redirect('/admin/navigation');
        }

        $order = Request::post('order', []);
        AccountNav::saveOrder($portal, is_array($order) ? $order : []);
        $labels = Request::post('label', []);
        AccountNav::saveLabels($portal, is_array($labels) ? $labels : []);
        flashSuccess('Menu saved for ' . AccountNav::portals()[$portal] . '. Everyone using it sees the new names and order straight away.');
        redirect('/admin/navigation?portal=' . rawurlencode($portal));
    }
}
