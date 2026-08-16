<?php

namespace App\Controllers\Account;

use App\Core\CustomerSession;
use App\Core\View;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController
{
    public function __construct()
    {
        CustomerSession::start();
    }

    public function index(): void
    {
        $customer = CustomerSession::require();
        $orders = Order::forCustomer((int) $customer['id']);
        $itemsByOrder = OrderItem::forOrders(array_column($orders, 'id'));

        View::render('account.orders', [
            'pageTitle' => 'My Orders',
            'customer' => $customer,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
        ]);
    }
}
