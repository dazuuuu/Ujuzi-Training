<?php

namespace App\Services;

use App\Core\Database;
use App\Models\CourseEnrollment;

/**
 * Paying for a course one module at a time. A module's price is the course
 * fee divided by its number of modules (the last one takes any rounding, so
 * the total is exactly the fee). Money the student already put towards the
 * course is used first, then their wallet coins. A paid module stays open for
 * ACCESS_DAYS; once passed and past that, it moves to History, where the
 * student can still read it.
 */
class ModuleAccess
{
    public const ACCESS_DAYS = 30;

    /** @return array<int, array> module id => access row */
    public static function forCourse(int $userId, int $courseId): array
    {
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM course_module_access WHERE user_id = ? AND course_id = ?');
            $stmt->execute([$userId, $courseId]);
        } catch (\PDOException $e) {
            return []; // before the migration that adds per-module payments
        }
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['module_id']] = $row;
        }
        return $out;
    }

    /**
     * Whether modules need paying for one by one: a paid course that isn't
     * already fully paid (free courses, courses enrolled while payments were
     * off, and fully prepaid ones open every module without charge).
     */
    public static function chargesPerModule(int $userId, array $course): bool
    {
        if ((float) ($course['enrollment_fee_ksh'] ?? 0) <= 0) {
            return false;
        }
        $access = self::forCourse($userId, (int) $course['id']);
        // Once any module was bought, keep charging per module even after the fee is covered by it.
        if ($access) {
            return true;
        }
        return !CourseEnrollment::isSettled($userId, (int) $course['id']);
    }

    /**
     * The money side of a course for one student.
     * @return array{fee: float, paid: float, charged: float, credit: float, remaining: float, next_price: float, from_credit: float, from_wallet: float, standard_price: float}
     */
    public static function plan(int $userId, array $course, int $moduleCount): array
    {
        $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
        $access = self::forCourse($userId, (int) $course['id']);
        $paid = WalletService::paidForCourse($userId, (int) $course['id']);
        $charged = array_sum(array_map(static fn(array $a): float => (float) $a['price_ksh'], $access));
        $credit = max(0.0, round($paid - $charged, 2));
        $remaining = max(0.0, round($fee - $charged, 2));
        $standard = $moduleCount > 0 ? round($fee / $moduleCount, 2) : $fee;
        $unpaidModules = max(0, $moduleCount - count($access));
        $next = $unpaidModules <= 1 ? $remaining : min($remaining, $standard);
        $fromCredit = min($credit, $next);
        return [
            'fee' => $fee,
            'paid' => $paid,
            'charged' => $charged,
            'credit' => $credit,
            'remaining' => $remaining,
            'standard_price' => $standard,
            'next_price' => round($next, 2),
            'from_credit' => round($fromCredit, 2),
            'from_wallet' => round($next - $fromCredit, 2),
        ];
    }

    /**
     * Pays for and opens a module. The caller has already checked it's the
     * next module in order. Throws WalletException if the wallet is short.
     */
    public static function unlock(int $userId, array $course, array $module, int $moduleCount): array
    {
        $access = self::forCourse($userId, (int) $course['id']);
        if (isset($access[(int) $module['id']])) {
            return $access[(int) $module['id']];
        }
        $plan = self::plan($userId, $course, $moduleCount);
        $balance = WalletService::balanceKsh($userId);
        if ($plan['from_wallet'] > $balance + 0.001) {
            throw new WalletException(sprintf(
                'This module costs %s coins (Ksh %s) and your wallet has %s coins (Ksh %s). Deposit to keep learning.',
                self::coins($plan['from_wallet']), number_format($plan['from_wallet'], 2),
                self::coins($balance), number_format($balance, 2)
            ));
        }

        $reference = null;
        if ($plan['from_wallet'] > 0) {
            $reference = WalletService::recordCoursePayment($userId, $course, $plan['from_wallet'], ($course['title'] ?? 'Course') . ' — ' . ($module['title'] ?? 'module'));
        }
        Database::connection()->prepare(
            'INSERT INTO course_module_access (user_id, course_id, module_id, price_ksh, from_wallet_ksh, unlocked_at, expires_at)
             VALUES (?, ?, ?, ?, ?, NOW(), ' . ($plan['next_price'] > 0 ? 'NOW() + INTERVAL ' . self::ACCESS_DAYS . ' DAY' : 'NULL') . ')'
        )->execute([$userId, (int) $course['id'], (int) $module['id'], $plan['next_price'], $plan['from_wallet']]);
        WalletService::syncEnrollment($userId, $course, $reference);

        return self::forCourse($userId, (int) $course['id'])[(int) $module['id']];
    }

    /**
     * Each module's share of a fee, in order: the fee split evenly, with the
     * last module taking the rounding so they add up to the fee exactly.
     * @return float[]
     */
    public static function prices(float $fee, int $moduleCount): array
    {
        if ($moduleCount < 1) {
            return [];
        }
        $standard = round($fee / $moduleCount, 2);
        $prices = array_fill(0, $moduleCount, $standard);
        $prices[$moduleCount - 1] = round($fee - $standard * ($moduleCount - 1), 2);
        return $prices;
    }

    /** Coins shown to students for a Ksh amount, without trailing zeros. */
    public static function coins(float $ksh): string
    {
        return rtrim(rtrim(number_format(WalletService::coins($ksh), 2), '0'), '.') ?: '0';
    }

    /**
     * Annotates modules (already run through CourseModule::withUnlockState)
     * with payment state: is_paid, needs_payment, in_history, access.
     */
    public static function annotate(int $userId, array $course, array $modules): array
    {
        $perModule = self::chargesPerModule($userId, $course);
        $access = self::forCourse($userId, (int) $course['id']);
        $now = time();
        foreach ($modules as &$module) {
            $row = $access[(int) $module['id']] ?? null;
            $module['access'] = $row;
            // A module passed before per-module payments existed opens without paying again;
            // its share is still collected, because the last module's price covers what remains.
            $module['is_paid'] = !$perModule || $row !== null || !empty($module['is_done']);
            $module['needs_payment'] = !$module['is_paid'];
            $expires = $row && !empty($row['expires_at']) ? strtotime((string) $row['expires_at']) : null;
            $module['access_expires_at'] = $expires;
            $module['in_history'] = !empty($module['is_done']) && $expires !== null && $expires < $now;
        }
        unset($module);
        return $modules;
    }
}
