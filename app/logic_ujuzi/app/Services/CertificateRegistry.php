<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Course;
use App\Models\Organisation;
use App\Models\User;

/**
 * Every certificate in the system, for listing, filtering and downloading:
 *  - earned: a student finished a course that gives a certificate (and its
 *    attachment, when it needs one);
 *  - issued: Super Admin generated it for anyone by name, in a batch.
 */
class CertificateRegistry
{
    /**
     * Earned certificates, newest completion first. Filters: organisation_id,
     * course_id, q (name, reg. no., phone, course), from / to (Y-m-d,
     * completion date), user_ids (only these students).
     * Rows: user_id, course_id, name, registration_number, phone, email,
     * course_title, organisation_id, organisation_name, completed_at, certificate_no.
     */
    public static function earned(array $filters = []): array
    {
        $where = ['c.certificate_enabled = 1'];
        $params = [];
        if (!empty($filters['organisation_id'])) {
            $where[] = 'c.organisation_id = ?';
            $params[] = (int) $filters['organisation_id'];
        }
        if (!empty($filters['course_id'])) {
            $where[] = 'c.id = ?';
            $params[] = (int) $filters['course_id'];
        }
        try {
            $stmt = Database::connection()->prepare(
                "SELECT x.user_id, c.id AS course_id, MAX(x.completed_at) AS completed_at FROM (
                    SELECT e.user_id, e.course_id, NULL AS completed_at FROM course_enrollments e
                    UNION ALL
                    SELECT cc.user_id, cc.course_id, cc.completed_at FROM course_completions cc
                 ) x
                 INNER JOIN courses c ON c.id = x.course_id
                 WHERE " . implode(' AND ', $where) . '
                 GROUP BY x.user_id, c.id'
            );
            $stmt->execute($params);
            $pairs = $stmt->fetchAll();
        } catch (\Throwable $e) {
            return []; // before the completions update
        }

        $onlyUsers = isset($filters['user_ids']) && is_array($filters['user_ids']) ? array_flip(array_map('intval', $filters['user_ids'])) : null;
        $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        $courses = [];
        $out = [];
        foreach ($pairs as $pair) {
            $userId = (int) $pair['user_id'];
            $courseId = (int) $pair['course_id'];
            if ($onlyUsers !== null && !isset($onlyUsers[$userId])) {
                continue;
            }
            $courses[$courseId] ??= Course::find($courseId);
            $course = $courses[$courseId];
            if (!$course || !Course::isCertifiableFor($userId, $course)) {
                continue;
            }
            $student = User::find($userId);
            if (!$student) {
                continue;
            }
            $completed = $pair['completed_at'] ? substr((string) $pair['completed_at'], 0, 10) : '';
            if (($from !== '' && ($completed === '' || $completed < $from)) || ($to !== '' && ($completed === '' || $completed > $to))) {
                continue;
            }
            $row = [
                'user_id' => $userId,
                'course_id' => $courseId,
                'name' => userFullName($student),
                'registration_number' => (string) ($student['registration_number'] ?? ''),
                'phone' => (string) ($student['phone'] ?? ''),
                'email' => (string) ($student['email'] ?? ''),
                'course_title' => (string) $course['title'],
                'organisation_id' => (int) ($course['organisation_id'] ?? 0),
                'organisation_name' => (string) ($course['organisation_name'] ?? ''),
                'completed_at' => $completed,
                'certificate_no' => (string) (self::earnedRecord($userId, $courseId, CertificateService::branchIdFor($course, $userId))['certificate_no'] ?? ''),
            ];
            if ($q !== '' && !str_contains(mb_strtolower(implode(' ', [$row['certificate_no'], $row['name'], $row['registration_number'], $row['phone'], $row['email'], $row['course_title'], $row['organisation_name']])), $q)) {
                continue;
            }
            $out[] = $row;
        }
        usort($out, static fn(array $a, array $b): int => [$b['completed_at'], $a['name']] <=> [$a['completed_at'], $b['name']]);
        return $out;
    }

    private const NUMBER_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O or 1/I to misread

    /**
     * The earned certificate's own number (and the branch it was issued
     * under), created the first time the certificate is shown. The number
     * never changes, so it can be checked on the public lookup page.
     * Returns null before the certificate-numbers update.
     */
    public static function earnedRecord(int $userId, int $courseId, ?int $branchId = null): ?array
    {
        if ($userId < 1 || $courseId < 1) {
            return null;
        }
        try {
            $pdo = Database::connection();
            $find = $pdo->prepare('SELECT certificate_no, branch_id FROM earned_certificates WHERE user_id = ? AND course_id = ? LIMIT 1');
            $find->execute([$userId, $courseId]);
            $row = $find->fetch();
            if ($row) {
                return $row;
            }
            $insert = $pdo->prepare('INSERT INTO earned_certificates (certificate_no, user_id, course_id, branch_id) VALUES (?, ?, ?, ?)');
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= self::NUMBER_ALPHABET[random_int(0, strlen(self::NUMBER_ALPHABET) - 1)];
                }
                $number = 'UJC-' . date('y') . '-' . $code;
                try {
                    $insert->execute([$number, $userId, $courseId, $branchId ?: null]);
                    return ['certificate_no' => $number, 'branch_id' => $branchId ?: null];
                } catch (\PDOException $e) {
                    // Another request issued it first, or (rarely) the number is taken: look again.
                    $find->execute([$userId, $courseId]);
                    $row = $find->fetch();
                    if ($row) {
                        return $row;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null; // before the certificate-numbers update
        }
        return null;
    }

    /** The earned certificate with this number: [user_id, course_id, certificate_no, branch_id, created_at], or null. */
    public static function findEarnedByNumber(string $number): ?array
    {
        $number = strtoupper(trim($number));
        if ($number === '') {
            return null;
        }
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM earned_certificates WHERE certificate_no = ? LIMIT 1');
            $stmt->execute([$number]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The certificate (payload) for one earned row. */
    public static function earnedPayload(int $userId, int $courseId): ?array
    {
        $course = Course::find($courseId);
        $student = User::find($userId);
        if (!$course || !$student || !Course::isCertifiableFor($userId, $course)) {
            return null;
        }
        return CertificateService::payload($student, [$course]);
    }

    /**
     * Super Admin's certificates for anyone, one per name, all in one batch.
     * $people: [name, id_number]. Returns the batch id.
     */
    public static function issue(array $people, string $skills, ?int $organisationId, string $issuedOn, int $adminId): string
    {
        $pdo = Database::connection();
        $batch = date('ymd') . strtoupper(bin2hex(random_bytes(3)));
        $orgName = null;
        if ($organisationId) {
            $orgName = (string) (Organisation::find($organisationId)['name'] ?? '') ?: null;
        }
        $insert = $pdo->prepare(
            'INSERT INTO issued_certificates (certificate_no, batch_id, recipient_name, id_number, skills, organisation_id, organisation_name, issued_on, issued_by_admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($people as $n => $person) {
            $insert->execute([
                'UJC-' . $batch . '-' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                $batch,
                mb_substr($person['name'], 0, 190),
                ($person['id_number'] ?? '') !== '' ? mb_substr($person['id_number'], 0, 80) : null,
                mb_substr($skills, 0, 500),
                $organisationId ?: null,
                $orgName,
                $issuedOn,
                $adminId,
            ]);
        }
        return $batch;
    }

    /** Issued certificates. Filters: batch, q, organisation_id, from / to (issue date), ids. */
    public static function issued(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['batch'])) {
            $where[] = 'batch_id = ?';
            $params[] = (string) $filters['batch'];
        }
        if (!empty($filters['organisation_id'])) {
            $where[] = 'organisation_id = ?';
            $params[] = (int) $filters['organisation_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(recipient_name LIKE ? OR id_number LIKE ? OR skills LIKE ? OR certificate_no LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filters['from'])) {
            $where[] = 'issued_on >= ?';
            $params[] = (string) $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'issued_on <= ?';
            $params[] = (string) $filters['to'];
        }
        if (isset($filters['ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $filters['ids'])));
            $where[] = $ids ? 'id IN (' . implode(',', $ids) . ')' : '1 = 0';
        }
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM issued_certificates WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC, id ASC');
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return []; // before the certificates update
        }
    }

    /** The certificate (payload) for an issued row: on its organisation's design, else the platform's. */
    public static function issuedPayload(array $row): array
    {
        $organisationId = !empty($row['organisation_id']) ? (int) $row['organisation_id'] : null;
        $template = CertificateService::templatePath($organisationId);
        $skills = array_values(array_filter(array_map('trim', explode(',', (string) $row['skills']))));
        return [
            'learner' => (string) $row['recipient_name'],
            'registration_number' => (string) ($row['id_number'] ?: $row['certificate_no']),
            'certificate_no' => (string) $row['certificate_no'],
            'organisation' => '',
            'skills' => $skills,
            'course' => (string) $row['skills'],
            'course_organisation' => (string) ($row['organisation_name'] ?? ''),
            'issued' => date('j F Y', strtotime((string) $row['issued_on'])),
            'template' => $template,
            'is_pdf' => $template ? CertificateService::isPdf($template) : false,
            'is_image' => $template ? CertificateService::isImage($template) : false,
            'layout' => DocumentLayout::get('certificate', CertificateService::designOwner($organisationId)),
        ];
    }
}
