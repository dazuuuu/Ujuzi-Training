<?php

namespace App\Services;

use App\Core\Database;
use App\Models\CourseEnrollment;
use App\Models\StoreSetting;

class WalletException extends \Exception {}

/**
 * Money is stored in Ksh; "coins" are just how Ksh is shown to users
 * (default: Ksh 100 = 2 coins, changeable by Super Admin -> Finance).
 */
class WalletService
{
    public const MIN_DEPOSIT_KSH = 10;
    public const DEFAULT_MIN_PAYMENT_PERCENT = 40;

    /**
     * The share of a course fee a student must pay to enrol, in percent.
     * Super Admin sets it under Finance. The same share of a student's total
     * fees is what they must have paid before sending an attachment request.
     */
    public static function minPaymentPercent(): int
    {
        try {
            $value = (int) StoreSetting::get('min_first_payment_percent', (string) self::DEFAULT_MIN_PAYMENT_PERCENT);
        } catch (\Throwable $e) {
            $value = self::DEFAULT_MIN_PAYMENT_PERCENT;
        }
        return max(1, min(100, $value ?: self::DEFAULT_MIN_PAYMENT_PERCENT));
    }

    public static function minPaymentRatio(): float
    {
        return self::minPaymentPercent() / 100;
    }

    public static function setMinPaymentPercent(int $percent): void
    {
        StoreSetting::set('min_first_payment_percent', (string) max(1, min(100, $percent)));
    }

    public static function coinsPer100(): float
    {
        $rate = (float) StoreSetting::get('coins_per_100_ksh', '2');
        return $rate > 0 ? $rate : 2.0;
    }

    public static function coins(float $ksh): float
    {
        return round($ksh * self::coinsPer100() / 100, 2);
    }

    public static function balanceKsh(int $userId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount_ksh WHEN type = 'course_payment' THEN -amount_ksh ELSE 0 END), 0)
             FROM wallet_transactions WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        return (float) $stmt->fetchColumn();
    }

    public static function totalsFor(int $userId, string $type): float
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(SUM(amount_ksh), 0) FROM wallet_transactions WHERE user_id = ? AND type = ?');
        $stmt->execute([$userId, $type]);
        return (float) $stmt->fetchColumn();
    }

    public static function deposit(int $userId, float $amountKsh, string $phone): array
    {
        $amountKsh = round($amountKsh, 2);
        if ($amountKsh < self::MIN_DEPOSIT_KSH) {
            throw new WalletException('The minimum deposit is Ksh ' . self::MIN_DEPOSIT_KSH . '.');
        }
        if (!preg_match('/^(\+?254|0)?[71]\d{8}$/', preg_replace('/\s+/', '', $phone))) {
            throw new WalletException('Enter a valid M-Pesa phone number, e.g. 0712345678.');
        }
        try {
            $payment = PaymentGateway::charge($phone, $amountKsh);
        } catch (PaymentException $e) {
            throw new WalletException($e->getMessage());
        }
        Database::connection()->prepare(
            "INSERT INTO wallet_transactions (user_id, type, amount_ksh, provider, reference, note)
             VALUES (?, 'deposit', ?, ?, ?, ?)"
        )->execute([$userId, $amountKsh, $payment['provider'], $payment['reference'], 'Deposit from ' . $phone]);
        return $payment;
    }

    public static function paidForCourse(int $userId, int $courseId): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(amount_ksh), 0) FROM wallet_transactions WHERE user_id = ? AND course_id = ? AND type = 'course_payment' AND archived_at IS NULL"
        );
        $stmt->execute([$userId, $courseId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * The minimum deposit for a course (its share of the fee, see
     * minPaymentPercent), less anything already paid. Enrolling needs at least
     * this much in the wallet; see enrolmentDeposit().
     */
    public static function minimumPayment(array $course, float $alreadyPaid): float
    {
        $fee = (float) $course['enrollment_fee_ksh'];
        $remaining = max(0, $fee - $alreadyPaid);
        if ($alreadyPaid > 0) {
            return min(1.0, $remaining);
        }
        return min($remaining, ceil($fee * self::minPaymentRatio()));
    }

    /**
     * Pays part of a course fee ahead of time from the wallet (any amount up
     * to what's still owed). Modules then use this money before the wallet.
     * Enrols the student if they weren't already.
     */
    public static function payCourse(int $studentId, array $course, float $amountKsh): void
    {
        $amountKsh = round($amountKsh, 2);
        $fee = (float) $course['enrollment_fee_ksh'];
        $paid = self::paidForCourse($studentId, (int) $course['id']);
        $remaining = round($fee - $paid, 2);

        if ($remaining <= 0) {
            throw new WalletException('This course is already fully paid.');
        }
        if ($amountKsh <= 0) {
            throw new WalletException('Enter an amount to pay.');
        }
        if ($amountKsh > $remaining) {
            throw new WalletException('That is more than the balance of Ksh ' . number_format($remaining, 2) . '.');
        }
        if (self::balanceKsh($studentId) < $amountKsh) {
            throw new WalletException('Your wallet does not have enough coins. Deposit first.');
        }

        $reference = self::recordCoursePayment($studentId, $course, $amountKsh, (string) ($course['title'] ?? 'Course payment'));
        self::syncEnrollment($studentId, $course, $reference);
    }

    /**
     * Writes a course payment: a debit on the student's wallet and the same
     * amount credited to the course's tutor/organisation. Returns the reference.
     */
    public static function recordCoursePayment(int $studentId, array $course, float $amountKsh, string $note): string
    {
        $reference = 'PAY' . strtoupper(bin2hex(random_bytes(5)));
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO wallet_transactions (user_id, type, amount_ksh, course_id, organisation_id, payer_user_id, provider, reference, note)
                 VALUES (?, ?, ?, ?, ?, ?, \'wallet\', ?, ?)'
            );
            $insert->execute([$studentId, 'course_payment', $amountKsh, $course['id'], $course['organisation_id'], $studentId, $reference, $note]);
            $insert->execute([$course['trainer_user_id'], 'earning', $amountKsh, $course['id'], $course['organisation_id'], $studentId, $reference, $note]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $reference;
    }

    /** Keeps the enrollment row's amount and status in step with what has been paid. */
    public static function syncEnrollment(int $studentId, array $course, ?string $reference = null): void
    {
        $fee = (float) $course['enrollment_fee_ksh'];
        $paid = self::paidForCourse($studentId, (int) $course['id']);
        CourseEnrollment::enroll($studentId, (int) $course['id'], [
            'amount_ksh' => $paid,
            'payment_provider' => 'wallet',
            'payment_status' => $paid >= $fee ? 'paid' : 'partial',
            'payment_reference' => $reference,
        ]);
    }

    /** The coins a student must hold to enrol on a paid course: the minimum share of its fee. */
    public static function enrolmentDeposit(array $course): float
    {
        return ceil((float) $course['enrollment_fee_ksh'] * self::minPaymentRatio());
    }

    /** Transactions for one user's wallet page, newest first. */
    public static function transactionsFor(int $userId, int $limit = 100): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.*, c.title AS course_title, u.first_name AS payer_first, u.last_name AS payer_last, u.email AS payer_email
             FROM wallet_transactions t
             LEFT JOIN courses c ON c.id = t.course_id
             LEFT JOIN users u ON u.id = t.payer_user_id
             WHERE t.user_id = ? ORDER BY t.id DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Courses a student has started paying for, with what is still owed. */
    public static function studentCourses(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.id, c.title, c.enrollment_fee_ksh AS fee,
                    COALESCE((SELECT SUM(t.amount_ksh) FROM wallet_transactions t
                              WHERE t.user_id = ? AND t.course_id = c.id AND t.type = 'course_payment' AND t.archived_at IS NULL), 0) AS paid
             FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id
             WHERE e.user_id = ? AND c.enrollment_fee_ksh > 0 ORDER BY e.enrolled_at DESC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Every enrollment the branch admin is responsible for: the students
     * approved into this branch, the organisation's courses they enrolled for,
     * and what each of them still owes.
     */
    public static function branchEnrollments(int $organisationId, int $branchId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT u.id AS student_id, u.first_name, u.last_name, u.email, u.phone,
                    b.title AS branch_title,
                    c.id AS course_id, c.title AS course_title, c.enrollment_fee_ksh AS fee,
                    e.enrolled_at, e.payment_status,
                    COALESCE((SELECT SUM(t.amount_ksh) FROM wallet_transactions t
                              WHERE t.user_id = u.id AND t.course_id = c.id AND t.type = 'course_payment' AND t.archived_at IS NULL), 0) AS paid
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
             INNER JOIN course_enrollments e ON e.user_id = u.id
             INNER JOIN courses c ON c.id = e.course_id AND c.organisation_id = m.organisation_id
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.organisation_id = ? AND m.branch_id = ? AND m.status = 'approved'
             ORDER BY u.first_name, u.last_name, c.title"
        );
        $stmt->execute([$organisationId, $branchId]);

        return array_map(static function (array $row): array {
            $fee = (float) $row['fee'];
            // Free courses and ones enrolled while payments were closed are settled on enrollment.
            $paid = (string) $row['payment_status'] === 'paid' ? $fee : (float) $row['paid'];
            $row['fee'] = $fee;
            $row['paid'] = $paid;
            $row['balance'] = round(max(0, $fee - $paid), 2);
            $row['is_settled'] = $row['balance'] <= 0;
            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Course-fee standing and progress for each student, across every course
     * they enrolled for: user id => [courses, fee, paid, balance, is_settled,
     * paid_ratio, below_minimum, items]. items lists each course with its fee,
     * paid, balance and progress. below_minimum is true while the student has
     * paid less than the minimum share (minPaymentPercent) of their total
     * fees. Students with no enrollment are
     * listed with zeros.
     */
    public static function feeSummaries(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $out = [];
        foreach ($userIds as $id) {
            $out[$id] = ['courses' => 0, 'fee' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'is_settled' => true,
                         'paid_ratio' => 1.0, 'below_minimum' => false, 'items' => []];
        }
        if (!$userIds) {
            return $out;
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT e.user_id, e.course_id, e.payment_status, c.title, c.cover_image, c.enrollment_fee_ksh AS fee,
                    c.final_exam_questions,
                    COALESCE((SELECT SUM(t.amount_ksh) FROM wallet_transactions t
                              WHERE t.user_id = e.user_id AND t.course_id = e.course_id AND t.type = 'course_payment' AND t.archived_at IS NULL), 0) AS paid,
                    (SELECT COUNT(*) FROM course_modules m WHERE m.course_id = e.course_id) AS modules_total,
                    (SELECT COUNT(*) FROM course_module_progress p
                       INNER JOIN course_modules m ON m.id = p.module_id
                       WHERE p.user_id = e.user_id AND p.course_id = e.course_id AND p.passed = 1) AS modules_passed,
                    COALESCE((SELECT f.passed FROM course_final_exam_progress f
                              WHERE f.user_id = e.user_id AND f.course_id = e.course_id LIMIT 1), 0) AS final_passed
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id
             WHERE e.user_id IN ($placeholders)
             ORDER BY e.enrolled_at ASC"
        );
        $stmt->execute($userIds);
        foreach ($stmt->fetchAll() as $row) {
            $id = (int) $row['user_id'];
            $fee = (float) $row['fee'];
            // Same rule as branchEnrollments(): a 'paid' enrollment owes nothing.
            $paid = (string) $row['payment_status'] === 'paid' ? $fee : min($fee, (float) $row['paid']);

            // Progress: every module plus the final exam (when the course has one) is one step.
            $questions = json_decode((string) ($row['final_exam_questions'] ?? ''), true);
            $hasFinal = is_array($questions) && $questions !== [];
            $steps = (int) $row['modules_total'] + ($hasFinal ? 1 : 0);
            $done = min((int) $row['modules_passed'], (int) $row['modules_total']) + ($hasFinal && (int) $row['final_passed'] ? 1 : 0);

            $out[$id]['courses']++;
            $out[$id]['fee'] += $fee;
            $out[$id]['paid'] += $paid;
            $out[$id]['items'][] = [
                'course_id' => (int) $row['course_id'],
                'title' => (string) $row['title'],
                'cover_image' => $row['cover_image'] ?? null,
                'fee' => $fee,
                'paid' => $paid,
                'balance' => round(max(0, $fee - $paid), 2),
                // Each course's money stands on its own: what was paid for
                // one course never counts towards another.
                'paid_ratio' => $fee > 0 ? $paid / $fee : 1.0,
                'below_minimum' => $fee > 0 && $paid / $fee < self::minPaymentRatio(),
                'modules_total' => (int) $row['modules_total'],
                'modules_passed' => min((int) $row['modules_passed'], (int) $row['modules_total']),
                'final_passed' => $hasFinal && (int) $row['final_passed'] === 1,
                'progress' => $steps > 0 ? (int) round($done * 100 / $steps) : 0,
            ];
        }
        foreach ($out as &$summary) {
            $summary['balance'] = round(max(0, $summary['fee'] - $summary['paid']), 2);
            $summary['is_settled'] = $summary['balance'] <= 0;
            $summary['paid_ratio'] = $summary['fee'] > 0 ? $summary['paid'] / $summary['fee'] : 1.0;
            // True when any one course is below its own minimum (never a blend of courses).
            $summary['below_minimum'] = (bool) array_filter($summary['items'], static fn(array $i): bool => $i['below_minimum']);
            $summary['owing'] = array_values(array_filter($summary['items'], static fn(array $i): bool => $i['balance'] > 0));
        }
        unset($summary);
        return $out;
    }

    /**
     * Fees for the course an attachment request is about. Requests are per
     * course, so only that course counts; older requests without a course
     * fall back to the student's courses in the request's category. Returns
     * fee, paid, balance, paid_ratio, below_minimum and the titles.
     */
    public static function requestFees(int $studentId, ?int $categoryId, ?int $courseId = null): array
    {
        $summary = self::feeSummaries([$studentId])[$studentId] ?? ['items' => []];
        $courseIds = null;
        if ($courseId) {
            $courseIds = [$courseId];
        } elseif ($categoryId) {
            $stmt = Database::connection()->prepare(
                'SELECT DISTINCT e.course_id FROM course_enrollments e
                 INNER JOIN course_categories cc ON cc.course_id = e.course_id
                 WHERE e.user_id = ? AND cc.category_id = ?'
            );
            $stmt->execute([$studentId, $categoryId]);
            $courseIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        }
        $out = ['fee' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'titles' => []];
        foreach ($summary['items'] as $item) {
            if ($courseIds !== null && !in_array((int) $item['course_id'], $courseIds, true)) {
                continue;
            }
            $out['fee'] += $item['fee'];
            $out['paid'] += $item['paid'];
            $out['titles'][] = $item['title'];
        }
        $out['balance'] = round(max(0, $out['fee'] - $out['paid']), 2);
        $out['paid_ratio'] = $out['fee'] > 0 ? $out['paid'] / $out['fee'] : 1.0;
        $out['below_minimum'] = $out['fee'] > 0 && $out['paid_ratio'] < self::minPaymentRatio();
        return $out;
    }

    /** One course's fee standing for a student (see requestFees()). */
    public static function courseStanding(int $studentId, int $courseId): array
    {
        return self::requestFees($studentId, null, $courseId);
    }

    /** The refusal shown when marking a student completed while their course has a balance. */
    public static function outstandingMessage(float $balance): string
    {
        return 'The student has an outstanding balance of: Ksh ' . number_format($balance, 2) . ', please clear the balance then mark as completed.';
    }

    /** What the student still owes, all their courses' balances added up (each course's balance is its own). */
    public static function studentBalanceOwed(int $userId): float
    {
        return self::feeSummaries([$userId])[$userId]['balance'] ?? 0.0;
    }

    /**
     * Per-course money for a tutor (trainer_user_id) or a whole organisation.
     * expected = fee x enrolled students, collected = payments received.
     */
    /**
     * Per-course earnings for a tutor or an organisation. $from / $to (Y-m-d,
     * inclusive) limit the payments and enrolments counted; $studentIds limits
     * them to those students (a branch's own students).
     */
    public static function courseEarnings(?int $trainerId, ?int $organisationId, string $from = '', string $to = '', ?array $studentIds = null): array
    {
        $where = $trainerId !== null ? 'c.trainer_user_id = ?' : 'c.organisation_id = ?';
        [$payRange, $payParams] = self::periodSql('t.created_at', $from, $to, 't.payer_user_id', $studentIds);
        [$enrolRange, $enrolParams] = self::periodSql('e.enrolled_at', $from, $to, 'e.user_id', $studentIds);
        $stmt = Database::connection()->prepare(
            "SELECT c.id, c.title, c.enrollment_fee_ksh AS fee, u.first_name, u.last_name, u.email,
                    (SELECT COUNT(*) FROM course_enrollments e WHERE e.course_id = c.id$enrolRange) AS students,
                    COALESCE((SELECT SUM(t.amount_ksh) FROM wallet_transactions t WHERE t.course_id = c.id AND t.type = 'earning'$payRange), 0) AS collected
             FROM courses c INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE $where ORDER BY collected DESC, c.title"
        );
        $stmt->execute(array_merge($enrolParams, $payParams, [$trainerId ?? $organisationId]));
        return $stmt->fetchAll();
    }

    /** Earnings credited to a tutor or to any tutor of an organisation, newest first (same filters as courseEarnings()). */
    public static function earningLines(?int $trainerId, ?int $organisationId, int $limit = 100, string $from = '', string $to = '', ?array $studentIds = null): array
    {
        $where = $trainerId !== null ? 't.user_id = ?' : 't.organisation_id = ?';
        [$range, $params] = self::periodSql('t.created_at', $from, $to, 't.payer_user_id', $studentIds);
        $stmt = Database::connection()->prepare(
            "SELECT t.*, c.title AS course_title, s.first_name AS payer_first, s.last_name AS payer_last, s.email AS payer_email,
                    s.registration_number AS payer_registration_number,
                    tu.first_name AS tutor_first, tu.last_name AS tutor_last, tu.email AS tutor_email
             FROM wallet_transactions t
             LEFT JOIN courses c ON c.id = t.course_id
             LEFT JOIN users s ON s.id = t.payer_user_id
             INNER JOIN users tu ON tu.id = t.user_id
             WHERE t.type = 'earning' AND $where$range ORDER BY t.id DESC LIMIT " . (int) $limit
        );
        $stmt->execute(array_merge([$trainerId ?? $organisationId], $params));
        return $stmt->fetchAll();
    }

    /** " AND col >= ? AND col < ? AND who IN (…)" with its parameters. */
    private static function periodSql(string $column, string $from, string $to, string $whoColumn, ?array $studentIds): array
    {
        $sql = '';
        $params = [];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= " AND $column >= ?";
            $params[] = $from;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= " AND $column < ?";
            $params[] = date('Y-m-d', strtotime($to . ' +1 day'));
        }
        if ($studentIds !== null) {
            $ids = array_values(array_filter(array_map('intval', $studentIds)));
            $sql .= $ids ? " AND $whoColumn IN (" . implode(',', $ids) . ')' : ' AND 1 = 0';
        }
        return [$sql, $params];
    }

    public static function systemSummary(): array
    {
        $row = Database::connection()->query(
            "SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount_ksh END),0) AS deposited,
                    COALESCE(SUM(CASE WHEN type='course_payment' THEN amount_ksh END),0) AS course_payments
             FROM wallet_transactions"
        )->fetch();
        $deposited = (float) $row['deposited'];
        $paid = (float) $row['course_payments'];
        return ['deposited' => $deposited, 'earned' => $paid, 'unspent' => $deposited - $paid];
    }

    public static function organisationSummaries(): array
    {
        return Database::connection()->query(
            "SELECT o.id, o.name,
                    COALESCE((SELECT SUM(t.amount_ksh) FROM wallet_transactions t WHERE t.organisation_id = o.id AND t.type = 'earning'), 0) AS collected,
                    (SELECT COALESCE(SUM(c.enrollment_fee_ksh * (SELECT COUNT(*) FROM course_enrollments e WHERE e.course_id = c.id)), 0)
                       FROM courses c WHERE c.organisation_id = o.id) AS expected,
                    (SELECT COUNT(*) FROM courses c WHERE c.organisation_id = o.id) AS courses
             FROM organisations o
             WHERE EXISTS (SELECT 1 FROM courses c WHERE c.organisation_id = o.id)
                OR EXISTS (SELECT 1 FROM wallet_transactions t WHERE t.organisation_id = o.id)
             ORDER BY collected DESC, o.name"
        )->fetchAll();
    }

    public static function recentTransactions(int $limit = 50): array
    {
        return Database::connection()->query(
            "SELECT t.*, c.title AS course_title, u.first_name, u.last_name, u.email, o.name AS organisation_name
             FROM wallet_transactions t
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN courses c ON c.id = t.course_id
             LEFT JOIN organisations o ON o.id = t.organisation_id
             WHERE t.type IN ('deposit','course_payment') ORDER BY t.id DESC LIMIT " . (int) $limit
        )->fetchAll();
    }

    public static function setCoinsPer100(float $rate): void
    {
        StoreSetting::set('coins_per_100_ksh', (string) max(0.01, $rate));
    }
}
