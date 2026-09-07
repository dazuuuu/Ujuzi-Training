<?php

namespace App\Models;

use App\Core\Database;

class OrderItem
{
    public static function forOrder(int $orderId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array> keyed by order_id */
    public static function forOrders(array $orderIds): array
    {
        if (!$orderIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = Database::connection()->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
        $stmt->execute($orderIds);
        $grouped = [];
        foreach ($stmt->fetchAll() as $item) {
            $grouped[$item['order_id']][] = $item;
        }
        return $grouped;
    }

    public static function create(int $orderId, array $item): void
    {
        Database::connection()->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, product_image, color_name, size_label, quantity, unit_price)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $orderId, $item['product_id'], $item['product_name'], $item['product_image'],
            $item['color_name'], $item['size_label'], $item['quantity'], $item['unit_price'],
        ]);
    }
}
