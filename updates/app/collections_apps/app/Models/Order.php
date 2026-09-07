<?php

namespace App\Models;

use App\Core\Database;

class Order
{
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public static function forCustomer(int $customerId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function recent(int $limit = 8): array
    {
        $limit = (int) $limit;
        return Database::connection()->query(
            "SELECT o.*, c.email, c.phone, c.first_name, c.last_name
             FROM orders o JOIN customers c ON c.id = o.customer_id
             ORDER BY o.created_at DESC LIMIT {$limit}"
        )->fetchAll();
    }

    public static function all(?string $status = null): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT o.*, c.email, c.phone, c.first_name, c.last_name FROM orders o JOIN customers c ON c.id = o.customer_id';
        $params = [];
        if ($status && in_array($status, self::STATUSES, true)) {
            $sql .= ' WHERE o.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findByRef(string $ref): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, c.email, c.phone, c.first_name AS cust_first, c.last_name AS cust_last
             FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.order_ref = ?'
        );
        $stmt->execute([$ref]);
        return $stmt->fetch() ?: null;
    }

    public static function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        Database::connection()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO orders (order_ref, customer_id, subtotal, discount, shipping, total, currency, payment_method, shipping_address, shipping_city, shipping_postal_code, shipping_country)
             VALUES (:order_ref, :customer_id, :subtotal, :discount, :shipping, :total, :currency, :payment_method, :shipping_address, :shipping_city, :shipping_postal_code, :shipping_country)'
        )->execute($data);
        return (int) $pdo->lastInsertId();
    }

    public static function counts(): array
    {
        $pdo = Database::connection();
        return [
            'total' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        ];
    }
}
