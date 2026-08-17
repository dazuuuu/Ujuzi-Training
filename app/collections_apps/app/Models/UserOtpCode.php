<?php

namespace App\Models;

use App\Core\Database;

class UserOtpCode
{
    public static function generate(int $userId, string $purpose = 'login'): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Database::connection()
            ->prepare('INSERT INTO user_otp_codes (user_id, code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))')
            ->execute([$userId, password_hash($code, PASSWORD_DEFAULT), $purpose]);
        return $code;
    }

    public static function verify(int $userId, string $code, string $purpose = 'login'): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM user_otp_codes WHERE user_id = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 5'
        );
        $stmt->execute([$userId, $purpose]);
        foreach ($stmt->fetchAll() as $row) {
            if (password_verify($code, $row['code'])) {
                Database::connection()->prepare('UPDATE user_otp_codes SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
                return true;
            }
        }
        return false;
    }
}
