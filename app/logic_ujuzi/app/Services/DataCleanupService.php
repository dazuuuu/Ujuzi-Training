<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Role;
use PDO;

/**
 * Super Admin -> Data Cleanup. Each method wipes one whole category of live
 * data. Tables with a proper ON DELETE CASCADE foreign key clean themselves
 * up when the parent row goes (see the migrations for users/organisations);
 * the couple of relations that aren't FK-enforced (attachment-provider
 * branches, which are keyed by owner_user_id with no constraint) are cleaned
 * up explicitly here.
 */
class DataCleanupService
{
    public const STUDENTS = 'students';
    public const COURSES = 'courses';
    public const COURSE_ORGANISATIONS = 'course_organisations';
    public const ATTACHMENT_ORGANISATIONS = 'attachment_organisations';

    public static function categories(): array
    {
        return [
            self::STUDENTS => 'Students',
            self::COURSES => 'Courses',
            self::COURSE_ORGANISATIONS => 'Organisations providing courses',
            self::ATTACHMENT_ORGANISATIONS => 'Organisations providing attachment',
        ];
    }

    public static function counts(): array
    {
        $pdo = Database::connection();
        $studentRoleId = self::roleId('student');
        $orgAdminRoleId = self::roleId('organisation_admin');
        $attachmentRoleId = self::roleId('attachment_trainer');

        return [
            self::STUDENTS => $studentRoleId ? self::countUsersByRole($pdo, $studentRoleId) : 0,
            self::COURSES => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
            self::COURSE_ORGANISATIONS => (int) $pdo->query(
                "SELECT COUNT(DISTINCT o.id) FROM organisations o
                 INNER JOIN users u ON u.organisation_id = o.id
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'organisation_admin'"
            )->fetchColumn(),
            self::ATTACHMENT_ORGANISATIONS => $attachmentRoleId ? self::countUsersByRole($pdo, $attachmentRoleId) : 0,
        ];
    }

    /** @param string[] $categories */
    public static function wipe(array $categories): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            foreach ($categories as $category) {
                switch ($category) {
                    case self::STUDENTS:
                        self::deleteUsersByRoleSlug($pdo, 'student');
                        break;
                    case self::COURSES:
                        $pdo->exec('DELETE FROM courses');
                        break;
                    case self::COURSE_ORGANISATIONS:
                        self::deleteCourseOrganisations($pdo);
                        break;
                    case self::ATTACHMENT_ORGANISATIONS:
                        self::deleteAttachmentOrganisations($pdo);
                        break;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Full reset: deletes every organisation, user, and their dependent data
     * (courses, branches, applications, form responses, enrollments...).
     * Keeps admin accounts, roles, and the form templates so Super Admin can
     * still log in and the profile forms are ready for the next tenant.
     */
    public static function wipeEverything(): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            // Not FK-covered from the organisation side (owner_user_id has no
            // constraint), so clear every branch explicitly before anything else.
            $pdo->exec('DELETE FROM organisation_branches');
            // Cascades organisation_categories, courses (+ modules, enrollments,
            // attachment_applications, progress), memberships, admin invites.
            $pdo->exec('DELETE FROM organisations');
            // Cascades form_responses, otp_codes, any remaining courses/enrollments/
            // attachment_applications/memberships tied to a user.
            $pdo->exec('DELETE FROM users');

            foreach ([
                'organisations', 'organisation_categories', 'organisation_branches',
                'organisation_memberships', 'organisation_admin_invites',
                'users', 'courses', 'course_modules', 'course_enrollments',
                'attachment_applications', 'form_responses', 'user_otp_codes',
            ] as $table) {
                try {
                    $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                } catch (\Throwable $e) {
                    // Table may not exist on older installs — harmless to skip.
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function deleteCourseOrganisations(PDO $pdo): void
    {
        $roleId = self::roleId('organisation_admin');
        if (!$roleId) {
            return;
        }
        $orgIds = $pdo->query(
            "SELECT DISTINCT u.organisation_id FROM users u WHERE u.role_id = {$roleId} AND u.organisation_id IS NOT NULL"
        )->fetchAll(PDO::FETCH_COLUMN);

        self::deleteUsersByRoleSlug($pdo, 'organisation_admin');

        if ($orgIds) {
            $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
            // Cascades organisation_categories, courses (+ course_modules, enrollments,
            // attachment_applications, progress), organisation_branches, memberships, invites.
            $pdo->prepare("DELETE FROM organisations WHERE id IN ($placeholders)")->execute($orgIds);
        }
    }

    private static function deleteAttachmentOrganisations(PDO $pdo): void
    {
        $roleId = self::roleId('attachment_trainer');
        if (!$roleId) {
            return;
        }
        $providerIds = $pdo->query("SELECT id FROM users WHERE role_id = {$roleId}")->fetchAll(PDO::FETCH_COLUMN);

        if ($providerIds) {
            $placeholders = implode(',', array_fill(0, count($providerIds), '?'));

            // Branch admins assigned to these providers' branches are that
            // branch's own account, not a general role — remove them too so
            // they don't linger as orphaned "still showing" accounts.
            $stmt = $pdo->prepare(
                "SELECT branch_admin_user_id FROM organisation_branches
                 WHERE owner_type = 'attachment_provider' AND owner_user_id IN ($placeholders) AND branch_admin_user_id IS NOT NULL"
            );
            $stmt->execute($providerIds);
            $branchAdminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // organisation_branches with owner_type='attachment_provider' are keyed by
            // owner_user_id with no FK, so they need an explicit delete.
            $pdo->prepare(
                "DELETE FROM organisation_branches WHERE owner_type = 'attachment_provider' AND owner_user_id IN ($placeholders)"
            )->execute($providerIds);

            if ($branchAdminIds) {
                $adminPlaceholders = implode(',', array_fill(0, count($branchAdminIds), '?'));
                $pdo->prepare("DELETE FROM users WHERE id IN ($adminPlaceholders)")->execute($branchAdminIds);
            }
        }

        self::deleteUsersByRoleSlug($pdo, 'attachment_trainer');
    }

    private static function deleteUsersByRoleSlug(PDO $pdo, string $slug): void
    {
        $roleId = self::roleId($slug);
        if (!$roleId) {
            return;
        }
        $pdo->exec("DELETE FROM users WHERE role_id = {$roleId}");
    }

    private static function countUsersByRole(PDO $pdo, int $roleId): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
        $stmt->execute([$roleId]);
        return (int) $stmt->fetchColumn();
    }

    private static function roleId(string $slug): ?int
    {
        $role = Role::findBySlug($slug);
        return $role ? (int) $role['id'] : null;
    }
}
