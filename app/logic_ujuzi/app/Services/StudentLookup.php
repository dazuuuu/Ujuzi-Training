<?php

namespace App\Services;

use App\Core\Database;
use App\Models\AttachmentApplication;
use App\Models\Course;
use App\Models\CourseFinalExamProgress;
use App\Models\OrganisationCategory;
use App\Models\OrganisationMembership;
use App\Models\User;

/**
 * Everything about one student, found by the registration number, the
 * certificate number on their certificate, or their National ID / passport /
 * birth certificate number: details, photo, organisations, courses, fees, certificates
 * and attachments. Used by organisations, branch admins and Super Admin.
 */
class StudentLookup
{
    public static function find(string $registrationNumber): ?array
    {
        $number = strtoupper(trim($registrationNumber));
        if ($number === '') {
            return null;
        }
        $stmt = Database::connection()->prepare(
            "SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'student' AND UPPER(u.registration_number) = ? LIMIT 1"
        );
        $stmt->execute([$number]);
        $id = (int) $stmt->fetchColumn();
        if ($id < 1) {
            // The number of one of the student's certificates works too.
            $id = (int) (CertificateRegistry::findEarnedByNumber($number)['user_id'] ?? 0);
        }
        if ($id < 1) {
            $id = self::idByDocumentNumber($number);
        }
        $student = $id > 0 ? User::find($id) : null;
        if (!$student) {
            return null;
        }

        // Students who signed up before the profile phone was saved to their account.
        if (trim((string) ($student['phone'] ?? '')) === '') {
            $phone = User::phoneFromProfile($student);
            if ($phone !== '') {
                User::syncPhone($id, $phone);
                $student['phone'] = User::normalizePhone($phone);
            }
        }

        $certificates = [];
        foreach (Course::certifiableCompletedByLearner($student) as $course) {
            $progress = CourseFinalExamProgress::findForUserCourse($id, (int) $course['id']);
            $course['completed_on'] = $progress['completed_at'] ?? $progress['updated_at'] ?? null;
            $record = CertificateRegistry::earnedRecord($id, (int) $course['id'], CertificateService::branchIdFor($course, $id));
            $course['certificate_no'] = (string) ($record['certificate_no'] ?? '');
            $course['issued_under'] = CertificateService::organisationWithBranch((string) ($course['organisation_name'] ?? ''), $record ? ((int) ($record['branch_id'] ?? 0) ?: null) : CertificateService::branchIdFor($course, $id));
            $certificates[] = $course;
        }

        $memberships = [];
        foreach (OrganisationMembership::forUser($id) as $membership) {
            $membership['category_names'] = array_values(OrganisationCategory::namesByIds($membership['category_ids']));
            $memberships[] = $membership;
        }

        return [
            'student' => $student,
            'details' => User::profileDetails($student),
            'memberships' => $memberships,
            'fees' => WalletService::feeSummaries([$id])[$id] ?? null,
            'wallet' => WalletService::balanceKsh($id),
            'certificates' => $certificates,
            'attachments' => AttachmentApplication::allForStudent($id),
        ];
    }

    /** The student whose National ID, passport or birth certificate number (profile form) is $number, else 0. */
    public static function idByDocumentNumber(string $number): int
    {
        $wanted = preg_replace('/[^A-Z0-9]/', '', strtoupper($number));
        if (strlen($wanted) < 4) {
            return 0;
        }
        try {
            $stmt = Database::connection()->prepare(
                "SELECT fr.user_id, fr.answers, ff.field_key, ff.field_type FROM form_responses fr
                 INNER JOIN form_fields ff ON ff.form_id = fr.form_id
                 INNER JOIN users u ON u.id = fr.user_id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'student' AND fr.answers LIKE ?
                   AND (ff.field_type = 'id_document' OR LOWER(ff.label) LIKE '%national id%' OR LOWER(ff.label) LIKE '%passport%'
                        OR LOWER(ff.label) LIKE '%birth cert%' OR LOWER(ff.label) LIKE '%id number%' OR LOWER(ff.label) LIKE '%id no%')"
            );
            $stmt->execute(['%' . $wanted . '%']);
        } catch (\Throwable $e) {
            return 0;
        }
        foreach ($stmt->fetchAll() as $row) {
            $answers = json_decode((string) $row['answers'], true);
            $value = is_array($answers) ? ($answers[$row['field_key']] ?? null) : null;
            $value = is_array($value) ? ($value['number'] ?? '') : $value;
            if (is_scalar($value) && preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value)) === $wanted) {
                return (int) $row['user_id'];
            }
        }
        return 0;
    }
}
