<?php

namespace App\Services;

use App\Core\Database;
use App\Models\AttachmentApplication;
use App\Models\CourseEnrollment;

/**
 * The public "is this person really a student here?" check, by the number on
 * a certificate: a student's registration number, the certificate's own
 * number, or the number of a certificate Super Admin generated. Shows only what a certificate already
 * shows — name, photo, courses and certificates — never phone, email or fees.
 */
class PublicLookup
{
    public static function find(string $number): ?array
    {
        $number = strtoupper(trim($number));
        if ($number === '' || mb_strlen($number) > 60) {
            return null;
        }

        $student = StudentLookup::find($number);
        if ($student) {
            $s = $student['student'];
            $id = (int) $s['id'];
            $certificates = array_map(static fn(array $c): array => [
                'title' => (string) $c['title'],
                'organisation' => (string) (($c['issued_under'] ?? '') ?: ($c['organisation_name'] ?? '')),
                'date' => !empty($c['completed_on']) ? date('j M Y', strtotime((string) $c['completed_on'])) : '',
                'number' => (string) ($c['certificate_no'] ?? ''),
            ], $student['certificates']);
            // Looked up by a certificate number: that certificate is the one being checked.
            $earned = CertificateRegistry::findEarnedByNumber($number);
            if ($earned && (int) $earned['user_id'] === $id) {
                $match = array_values(array_filter($certificates, static fn(array $c): bool => $c['number'] === $earned['certificate_no']));
                if (!$match) {
                    return null; // the certificate is no longer valid (e.g. the course was retaken)
                }
                return [
                    'kind' => 'certificate',
                    'name' => userFullName($s),
                    'number' => (string) $earned['certificate_no'],
                    'registration_number' => (string) $s['registration_number'],
                    'photo' => !empty($s['photo_path']) ? (string) $s['photo_path'] : null,
                    'since' => '',
                    'certificates' => $match,
                    'studying' => [],
                    'attachments' => [],
                ];
            }
            $certified = array_flip(array_column($student['certificates'], 'id'));
            $studying = [];
            try {
                foreach (CourseEnrollment::idsForUser($id) as $courseId) {
                    $c = \App\Models\Course::find((int) $courseId);
                    if ($c && !isset($certified[$c['id']])) {
                        $studying[] = ['title' => (string) $c['title'], 'organisation' => (string) ($c['organisation_name'] ?? '')];
                    }
                }
            } catch (\Throwable $e) {
                // Enrolments are optional here.
            }
            $attachments = [];
            foreach (AttachmentApplication::allForStudent($id) as $a) {
                if (in_array($a['status'], [AttachmentApplication::STATUS_RECOMMENDED, AttachmentApplication::STATUS_COMPLETED], true)) {
                    $attachments[] = ['organisation' => (string) ($a['organisation_name'] ?: trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''))), 'course' => (string) ($a['course_title'] ?? '')];
                }
            }
            return [
                'kind' => 'student',
                'name' => userFullName($s),
                'number' => (string) $s['registration_number'],
                'photo' => !empty($s['photo_path']) ? (string) $s['photo_path'] : null,
                'since' => !empty($s['created_at']) ? date('F Y', strtotime((string) $s['created_at'])) : '',
                'certificates' => $certificates,
                'studying' => $studying,
                'attachments' => $attachments,
            ];
        }

        // A certificate Super Admin generated (possibly for someone without an account).
        try {
            $stmt = Database::connection()->prepare('SELECT * FROM issued_certificates WHERE UPPER(certificate_no) = ? LIMIT 1');
            $stmt->execute([$number]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            $row = false;
        }
        if ($row) {
            return [
                'kind' => 'certificate',
                'name' => (string) $row['recipient_name'],
                'number' => (string) $row['certificate_no'],
                'photo' => null,
                'since' => '',
                'certificates' => [['title' => (string) $row['skills'], 'organisation' => (string) ($row['organisation_name'] ?? ''), 'date' => date('j M Y', strtotime((string) $row['issued_on'])), 'number' => (string) $row['certificate_no']]],
                'studying' => [],
                'attachments' => [],
            ];
        }
        return null;
    }
}
