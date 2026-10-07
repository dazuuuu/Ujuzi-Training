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
 * Everything about one student, found by the registration number on their
 * certificate: details, photo, organisations, courses, fees, certificates
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
}
