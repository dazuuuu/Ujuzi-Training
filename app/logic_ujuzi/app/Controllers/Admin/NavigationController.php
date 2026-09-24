<?php

namespace App\Controllers\Admin;

use App\Core\AccountNav;
use App\Core\Request;
use App\Core\View;

class NavigationController extends BaseAdminController
{
    public function index(): void
    {
        $portals = [];
        foreach (AccountNav::portals() as $key => $label) {
            $portals[$key] = [
                'label' => $label,
                'items' => AccountNav::orderedItems($key),
            ];
        }

        View::render('admin.navigation.index', [
            'pageTitle' => 'Navigation',
            'activeNav' => 'navigation',
            'portals' => $portals,
        ]);
    }

    public function update(string $portal): void
    {
        if (!csrfVerify(Request::post('csrf_token'))) {
            flashError('Your session expired. Please try again.');
            redirect('/admin/navigation');
        }
        if (!array_key_exists($portal, AccountNav::portals())) {
            flashError('Unknown portal.');
            redirect('/admin/navigation');
        }

        $order = Request::post('order', []);
        $order = is_array($order) ? $order : [];
        AccountNav::saveOrder($portal, $order);
        flashSuccess('Menu order saved for ' . AccountNav::portals()[$portal] . '.');
        redirect('/admin/navigation');
    }
}
