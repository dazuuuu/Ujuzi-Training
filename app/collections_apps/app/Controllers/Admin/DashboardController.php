<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\View;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends BaseAdminController
{
    public function index(): void
    {
        Product::expireMaturedOffers();
        $pdo = Database::connection();
        $orderCounts = Order::counts();
        $totalRevenue = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
        $monthlyRevenue = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
        $todayOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
        $fulfilledOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
        $activeProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE in_stock = 1')->fetchColumn();
        $outOfStockProducts = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE in_stock = 0')->fetchColumn();

        $stats = [
            'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'onOffer' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_sale = 1')->fetchColumn(),
            'categories' => (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'orders' => $orderCounts['total'],
            'pendingOrders' => $orderCounts['pending'],
            'customers' => (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn(),
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'todayOrders' => $todayOrders,
            'fulfilledOrders' => $fulfilledOrders,
            'fulfillmentRate' => $orderCounts['total'] > 0 ? round(($fulfilledOrders / $orderCounts['total']) * 100, 1) : 0,
            'activeProducts' => $activeProducts,
            'outOfStockProducts' => $outOfStockProducts,
        ];

        $trendRows = $pdo->query(
            "SELECT DATE_FORMAT(created_at, '%b') AS month_label,
                    MONTH(created_at) AS month_number,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%b')
             ORDER BY YEAR(created_at), MONTH(created_at)"
        )->fetchAll();

        $categoryBreakdown = $pdo->query(
            'SELECT c.name, c.category_key, COUNT(p.id) AS product_count,
                    SUM(CASE WHEN p.is_sale = 1 THEN 1 ELSE 0 END) AS offer_count
             FROM categories c
             LEFT JOIN products p ON p.category_key = c.category_key
             GROUP BY c.id, c.name, c.category_key
             ORDER BY product_count DESC, c.sort_order ASC'
        )->fetchAll();

        $offerRows = $pdo->query(
            'SELECT id, name, product_code, price, original_price, offer_ends_at
             FROM products
             WHERE is_sale = 1
             ORDER BY offer_ends_at IS NULL ASC, offer_ends_at ASC, updated_at DESC
             LIMIT 5'
        )->fetchAll();

        View::render('admin.dashboard', [
            'pageTitle' => 'Dashboard',
            'activeNav' => 'dashboard',
            'stats' => $stats,
            'recentOrders' => Order::recent(8),
            'trendRows' => $trendRows,
            'categoryBreakdown' => $categoryBreakdown,
            'offerRows' => $offerRows,
        ]);
    }
}
