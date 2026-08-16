<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MailerException;
use App\Services\MailerService;
use PDOException;

/**
 * POST /api/place-order — called by assets/js/app.js's checkout flow.
 * Recomputes totals from the database (never trusts client-sent prices),
 * creates/updates the customer record from the checkout details, and
 * persists the order.
 */
class OrderController
{
    public function store(): void
    {
        header('Content-Type: application/json');

        $payload = Request::json();
        $items = $payload['items'] ?? [];
        $currency = in_array($payload['currency'] ?? '', ['KSH', 'USD', 'EUR', 'GBP'], true) ? $payload['currency'] : 'KSH';
        $discountPercent = max(0, min(100, (float) ($payload['discountPercent'] ?? 0)));
        $customerInput = $payload['customer'] ?? [];
        $paymentMethod = trim((string) ($payload['paymentMethod'] ?? ''));

        $email = trim((string) ($customerInput['email'] ?? ''));
        $phone = trim((string) ($customerInput['phone'] ?? ''));

        if ($email === '' && $phone === '') {
            $this->respond(['success' => false, 'error' => 'An email or phone number is required.'], 422);
        }
        if (!$items) {
            $this->respond(['success' => false, 'error' => 'Your cart is empty.'], 422);
        }

        $lineItems = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $product = Product::findByCode((string) ($item['productCode'] ?? ''));
            if (!$product) {
                $this->respond(['success' => false, 'error' => 'One of the items in your cart is no longer available.'], 422);
            }
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $subtotal += $product['price'] * $qty;
            $lineItems[] = [
                'product_id' => $product['dbId'],
                'product_name' => $product['name'],
                'product_image' => $product['images'][0] ?? null,
                'color_name' => (string) ($item['colorName'] ?? ''),
                'size_label' => (string) ($item['sizeLabel'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $product['price'],
            ];
        }

        $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        $shipping = ($subtotal - $discountAmount) >= 100 ? 0.0 : 15.0;
        $total = round($subtotal - $discountAmount + $shipping, 2);

        $pdo = Database::connection();
        $customerFields = [
            'first_name' => trim((string) ($customerInput['firstName'] ?? '')),
            'last_name' => trim((string) ($customerInput['lastName'] ?? '')),
            'address' => trim((string) ($customerInput['address'] ?? '')),
            'city' => trim((string) ($customerInput['city'] ?? '')),
            'postal_code' => trim((string) ($customerInput['postalCode'] ?? '')),
            'country' => trim((string) ($customerInput['country'] ?? '')),
        ];

        try {
            $pdo->beginTransaction();

            $customerId = Customer::findOrCreateFromCheckout($email, $phone, $customerFields);

            $orderRef = 'PTG-' . strtoupper(bin2hex(random_bytes(3)));
            $orderId = Order::create([
                'order_ref' => $orderRef,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'shipping' => $shipping,
                'total' => $total,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'shipping_address' => $customerFields['address'],
                'shipping_city' => $customerFields['city'],
                'shipping_postal_code' => $customerFields['postal_code'],
                'shipping_country' => $customerFields['country'],
            ]);

            foreach ($lineItems as $li) {
                OrderItem::create($orderId, $li);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $this->respond(['success' => false, 'error' => 'We could not process your order. Please try again.'], 500);
        }

        if ($email !== '') {
            try {
                foreach ($lineItems as &$li) {
                    $li['currency'] = $currency;
                }
                unset($li);
                MailerService::sendOrderConfirmation($email, ['order_ref' => $orderRef, 'total' => $total, 'currency' => $currency], $lineItems);
            } catch (MailerException $e) {
                // Non-fatal — the order is already saved even if the email fails to send.
            }
        }

        $this->respond([
            'success' => true,
            'orderRef' => $orderRef,
            'date' => date('M j, Y'),
            'total' => $total,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'shipping' => $shipping,
        ]);
    }

    private function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}
