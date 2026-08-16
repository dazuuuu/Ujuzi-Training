<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends BaseAdminController
{
    public function index(): void
    {
        $status = (string) Request::query('status', '');
        View::render('admin.orders.index', [
            'pageTitle' => 'Orders',
            'activeNav' => 'orders',
            'orders' => Order::all($status ?: null),
            'statuses' => Order::STATUSES,
            'statusFilter' => $status,
        ]);
    }

    public function show(string $ref): void
    {
        $order = Order::findByRef($ref);
        if (!$order) {
            redirect('/admin/orders');
        }
        View::render('admin.orders.show', [
            'pageTitle' => 'Order ' . $order['order_ref'],
            'activeNav' => 'orders',
            'order' => $order,
            'items' => OrderItem::forOrder((int) $order['id']),
            'statuses' => Order::STATUSES,
        ]);
    }

    public function updateStatus(string $id): void
    {
        if (csrfVerify(Request::post('csrf_token'))) {
            Order::updateStatus((int) $id, (string) Request::post('status', ''));
            flashSuccess('Order status updated.');
        }
        $ref = Request::post('order_ref', '');
        redirect($ref ? '/admin/orders/' . urlencode($ref) : '/admin/orders');
    }
}
