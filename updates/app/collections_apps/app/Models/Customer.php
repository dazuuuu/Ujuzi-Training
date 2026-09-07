<?php

namespace App\Models;

use App\Core\Database;
use PDOException;

class Customer
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdentifier(string $type, string $value): ?array
    {
        $column = $type === 'email' ? 'email' : 'phone';
        $stmt = Database::connection()->prepare("SELECT * FROM customers WHERE $column = ?");
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    public static function markEmailVerified(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE customers SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
            ->execute([$id]);
    }

    /**
     * Finds an existing customer by email then phone, or creates a new one —
     * used by checkout to auto-create the tracking account.
     */
    public static function findOrCreateFromCheckout(string $email, string $phone, array $fields): int
    {
        $pdo = Database::connection();
        $customer = $email !== '' ? self::findByIdentifier('email', $email) : null;
        if (!$customer && $phone !== '') {
            $customer = self::findByIdentifier('phone', $phone);
        }

        if ($customer) {
            $pdo->prepare('UPDATE customers SET first_name = ?, last_name = ?, address = ?, city = ?, postal_code = ?, country = ? WHERE id = ?')
                ->execute([$fields['first_name'], $fields['last_name'], $fields['address'], $fields['city'], $fields['postal_code'], $fields['country'], $customer['id']]);
            $customerId = (int) $customer['id'];

            if ($phone !== '' && empty($customer['phone'])) {
                try {
                    $pdo->prepare('UPDATE customers SET phone = ? WHERE id = ?')->execute([$phone, $customerId]);
                } catch (PDOException $e) {
                    // phone already used by a different customer — leave as-is.
                }
            }
            return $customerId;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO customers (email, phone, first_name, last_name, address, city, postal_code, country)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $email !== '' ? $email : null,
            $phone !== '' ? $phone : null,
            $fields['first_name'], $fields['last_name'], $fields['address'],
            $fields['city'], $fields['postal_code'], $fields['country'],
        ]);
        return (int) $pdo->lastInsertId();
    }
}
