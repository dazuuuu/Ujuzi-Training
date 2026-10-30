<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Url;
use App\Models\User;

/**
 * A receipt for every deposit and every course / module payment: numbered,
 * kept in the student's payment history (view, print, download) and emailed
 * to them straight away. A receipt that can't be emailed is still kept.
 */
class Receipts
{
    /** Records the receipt and emails it. Returns the receipt number, or null before the receipts update. */
    public static function issue(int $userId, string $kind, float $amountKsh, string $description, ?int $courseId = null, ?string $reference = null): ?string
    {
        try {
            $pdo = Database::connection();
            $count = (int) $pdo->query("SELECT COUNT(*) FROM payment_receipts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $number = 'RCT-' . date('Ymd') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            for ($try = 0; $try < 5; $try++) {
                try {
                    $pdo->prepare(
                        'INSERT INTO payment_receipts (receipt_no, user_id, course_id, description, amount_ksh, kind, reference) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([$number, $userId, $courseId, mb_substr($description, 0, 255), $amountKsh, $kind === 'deposit' ? 'deposit' : 'course_payment', $reference]);
                    break;
                } catch (\PDOException $e) {
                    $number = 'RCT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))); // two at the same moment
                }
            }
            $id = (int) $pdo->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
        self::email($id);
        return $number;
    }

    public static function find(int $id, ?int $userId = null): ?array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT r.*, c.title AS course_title, u.first_name, u.other_names, u.last_name, u.email, u.phone, u.registration_number
                 FROM payment_receipts r INNER JOIN users u ON u.id = r.user_id LEFT JOIN courses c ON c.id = r.course_id
                 WHERE r.id = ?' . ($userId !== null ? ' AND r.user_id = ' . (int) $userId : '')
            );
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function forUser(int $userId): array
    {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT r.*, c.title AS course_title FROM payment_receipts r LEFT JOIN courses c ON c.id = r.course_id
                 WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC'
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Emails the receipt (its details in the message, plus a button to view / print it). */
    private static function email(int $id): void
    {
        $r = self::find($id);
        if (!$r || trim((string) $r['email']) === '' || !MailerService::isConfigured()) {
            return;
        }
        try {
            MailerService::sendNotice(
                (string) $r['email'],
                'Receipt ' . $r['receipt_no'] . ' — Ksh ' . number_format((float) $r['amount_ksh'], 2),
                'Payment received — thank you',
                "Hello " . userFullName($r) . ",\n\nWe received your payment.\n\n"
                . "Receipt: " . $r['receipt_no'] . "\n"
                . "Date: " . date('j M Y, H:i', strtotime((string) $r['created_at'])) . "\n"
                . "For: " . $r['description'] . "\n"
                . "Amount: Ksh " . number_format((float) $r['amount_ksh'], 2) . "\n"
                . ($r['reference'] ? "Reference: " . $r['reference'] . "\n" : '')
                . "\nYou can view, print or download all your receipts under Payment history.",
                'View receipt',
                Url::absolute('/account/payments/' . $id)
            );
            Database::connection()->prepare('UPDATE payment_receipts SET emailed_at = NOW() WHERE id = ?')->execute([$id]);
        } catch (\Throwable $e) {
            // Kept in the payment history even when the email can't go.
        }
    }
}
