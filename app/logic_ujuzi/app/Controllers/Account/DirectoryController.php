<?php

namespace App\Controllers\Account;

use App\Core\Authz;
use App\Core\Database;
use App\Models\Course;
use App\Models\Organisation;
use App\Models\OrganisationBranch;
use App\Models\OrganisationCategory;
use App\Models\User;

/**
 * Details students open from a course or an organisation list: an
 * organisation's contacts and branches, and the tutor of a course.
 */
class DirectoryController extends BaseAccountController
{
    public function organisation(string $id): void
    {
        $organisation = Organisation::find((int) $id);
        if (!$organisation || empty($organisation['is_active'])) {
            flashError('That organisation could not be found.');
            redirect('/account/dashboard');
        }
        $orgId = (int) $organisation['id'];

        $stmt = Database::connection()->prepare(
            "SELECT u.id FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.organisation_id = ? AND r.slug = 'attachment_trainer' AND u.is_active = 1 LIMIT 1"
        );
        $stmt->execute([$orgId]);
        $providerId = (int) $stmt->fetchColumn();

        $branches = $providerId > 0
            ? OrganisationBranch::forAttachmentProvider($providerId)
            : OrganisationBranch::forOrganisation($orgId);

        $this->render('account.directory.organisation', [
            'pageTitle' => $organisation['name'],
            'activeNav' => $providerId > 0 ? 'attachment_providers' : 'course_organisations',
            'organisation' => $organisation,
            'isAttachmentOrg' => $providerId > 0,
            'branches' => $branches,
            'categories' => $providerId > 0 ? [] : OrganisationCategory::forOrganisation($orgId, true),
            'courseCount' => $providerId > 0 ? 0 : count(array_filter(
                Course::forOrganisation($orgId),
                static fn(array $c): bool => !empty($c['is_published'])
            )),
        ]);
    }

    public function tutor(string $id): void
    {
        $tutor = User::find((int) $id);
        if (!$tutor || ($tutor['role_slug'] ?? '') !== 'trainer' || !$this->maySeeTutor($tutor)) {
            flashError('You can see a tutor once you enrol in one of their courses.');
            redirect('/account/courses');
        }

        $courses = array_values(array_filter(
            Course::forTrainer((int) $tutor['id']),
            static fn(array $c): bool => !empty($c['is_published'])
        ));

        $this->render('account.directory.tutor', [
            'pageTitle' => userDisplayName($tutor),
            'activeNav' => 'courses',
            'tutor' => $tutor,
            'courses' => $courses,
        ]);
    }

    /** Students enrolled in one of the tutor's courses, the tutor's own organisation's staff, and the tutor. */
    private function maySeeTutor(array $tutor): bool
    {
        $viewerId = (int) $this->user['id'];
        if ($viewerId === (int) $tutor['id']) {
            return true;
        }
        if (Authz::isStudent($this->user)) {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM course_enrollments e INNER JOIN courses c ON c.id = e.course_id
                 WHERE e.user_id = ? AND c.trainer_user_id = ? LIMIT 1'
            );
            $stmt->execute([$viewerId, (int) $tutor['id']]);
            return (bool) $stmt->fetchColumn();
        }
        return !empty($this->user['organisation_id'])
            && (int) $this->user['organisation_id'] === (int) ($tutor['organisation_id'] ?? 0);
    }
}
