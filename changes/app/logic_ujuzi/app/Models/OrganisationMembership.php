<?php

namespace App\Models;

use App\Core\Database;

class OrganisationMembership
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Roles that ask to join an organisation providing courses and wait for
     * its approval. Attachment providers are not among them: they choose
     * where they appear themselves (AttachmentAudience), with no approval.
     */
    public static function trainerRoleSlugs(): array
    {
        return ['trainer'];
    }

    public static function isTrainerRole(string $slug): bool
    {
        return in_array($slug, self::trainerRoleSlugs(), true);
    }

    public static function isStudentRole(string $slug): bool
    {
        return $slug === 'student';
    }

    public static function selectedOrganisationIds(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT organisation_id FROM organisation_memberships
             WHERE user_id = ? AND status IN ('approved', 'pending')"
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.email, u.phone, u.first_name, u.last_name, u.organisation_id AS user_organisation_id,
                    r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name,
                    b.title AS branch_title
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN organisations o ON o.id = m.organisation_id
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, o.name AS organisation_name, b.title AS branch_title
             FROM organisation_memberships m
             INNER JOIN organisations o ON o.id = m.organisation_id
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.user_id = ?
             ORDER BY m.created_at DESC'
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrateCategoryIds'], $stmt->fetchAll());
    }

    /** The categories a user is approved for at one specific organisation — empty means no restriction (matches every category). */
    public static function approvedCategoryIdsFor(int $userId, int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT category_ids FROM organisation_memberships
             WHERE user_id = ? AND organisation_id = ? AND status = 'approved' LIMIT 1"
        );
        $stmt->execute([$userId, $organisationId]);
        $raw = $stmt->fetchColumn();
        if (!$raw) {
            return [];
        }
        $ids = json_decode((string) $raw, true);
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    private static function hydrateCategoryIds(array $row): array
    {
        $raw = $row['category_ids'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true) ?: [];
        }
        $row['category_ids'] = is_array($raw) ? array_map('intval', $raw) : [];
        return $row;
    }

    /** Approved students of one organisation (optionally limited to one branch), for editing their categories. */
    public static function approvedStudents(int $organisationId, ?int $branchId = null): array
    {
        $sql = "SELECT m.*, u.email, u.phone, u.first_name, u.last_name, b.title AS branch_title
                FROM organisation_memberships m
                INNER JOIN users u ON u.id = m.user_id
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisation_branches b ON b.id = m.branch_id
                WHERE m.organisation_id = ? AND m.status = 'approved' AND r.slug = 'student'";
        $params = [$organisationId];
        if ($branchId !== null) {
            $sql .= ' AND m.branch_id = ?';
            $params[] = $branchId;
        }
        $stmt = Database::connection()->prepare($sql . ' ORDER BY u.first_name, u.last_name');
        $stmt->execute($params);
        return array_map([self::class, 'hydrateCategoryIds'], $stmt->fetchAll());
    }

    /** Replaces the categories on an already-approved student membership. */
    public static function updateApprovedCategories(int $id, int $organisationId, array $categoryIds): bool
    {
        $row = self::find($id);
        if (!$row || (int) $row['organisation_id'] !== $organisationId || $row['status'] !== self::STATUS_APPROVED) {
            return false;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$ids) {
            return false;
        }
        Database::connection()->prepare('UPDATE organisation_memberships SET category_ids = ? WHERE id = ?')
            ->execute([json_encode($ids), $id]);
        return true;
    }

    /** Pending course-organisation join requests from students, for the organisation admin to approve. */
    public static function pendingStudentsForOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.*, u.email, u.phone, u.first_name, u.last_name,
                    u.role_id, r.slug AS role_slug, r.name AS role_name,
                    b.title AS branch_title
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.organisation_id = ?
               AND m.status = 'pending'
               AND r.slug = 'student'
             ORDER BY m.created_at DESC"
        );
        $stmt->execute([$organisationId]);
        return array_map([self::class, 'hydrateCategoryIds'], $stmt->fetchAll());
    }

    /** Pending student join requests for one branch — for that branch's own admin to review. */
    public static function pendingStudentsForBranch(int $branchId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT m.*, u.email, u.phone, u.first_name, u.last_name,
                    u.role_id, r.slug AS role_slug, r.name AS role_name,
                    b.title AS branch_title
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisation_branches b ON b.id = m.branch_id
             WHERE m.branch_id = ?
               AND m.status = 'pending'
               AND r.slug = 'student'
             ORDER BY m.created_at DESC"
        );
        $stmt->execute([$branchId]);
        return array_map([self::class, 'hydrateCategoryIds'], $stmt->fetchAll());
    }

    /**
     * A student picking an organisation that provides courses, with the
     * course categories they're interested in. Starts pending for the
     * organisation admin to review — UNLESS the student was already an
     * approved member of that organisation (e.g. their account was created
     * directly by that organisation), in which case picking categories is
     * self-service and stays approved with no re-review needed.
     */
    public static function requestOrganisation(int $userId, int $organisationId, ?int $branchId, array $categoryIds): bool
    {
        if ($userId < 1 || $organisationId < 1) {
            return false;
        }
        $categoryJson = $categoryIds ? json_encode(array_values(array_map('intval', $categoryIds))) : null;

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, status FROM organisation_memberships WHERE user_id = ? AND organisation_id = ? LIMIT 1');
        $stmt->execute([$userId, $organisationId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === self::STATUS_APPROVED) {
                $pdo->prepare(
                    'UPDATE organisation_memberships SET branch_id = ?, category_ids = ? WHERE id = ?'
                )->execute([$branchId, $categoryJson, (int) $existing['id']]);
                return true;
            }
            $pdo->prepare(
                "UPDATE organisation_memberships
                 SET status = 'pending', branch_id = ?, category_ids = ?, reviewed_at = NULL, reviewed_by_user_id = NULL
                 WHERE id = ?"
            )->execute([$branchId, $categoryJson, (int) $existing['id']]);
            return true;
        }

        $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, branch_id, category_ids, status)
             VALUES (?, ?, ?, ?, 'pending')"
        )->execute([$userId, $organisationId, $branchId, $categoryJson]);
        return true;
    }

    public static function pendingTrainersForOrganisation(int $organisationId): array
    {
        self::syncProfileSelectionsForOrganisation($organisationId);
        $placeholders = implode(',', array_fill(0, count(self::trainerRoleSlugs()), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT m.*, u.email, u.phone, u.first_name, u.last_name,
                    u.role_id, r.slug AS role_slug, r.name AS role_name
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE m.organisation_id = ?
               AND m.status = 'pending'
               AND r.slug IN ($placeholders)
             ORDER BY m.created_at DESC"
        );
        $stmt->execute(array_merge([$organisationId], self::trainerRoleSlugs()));
        $requests = $stmt->fetchAll();
        foreach ($requests as &$request) {
            $request['profile_details'] = self::profileDetailsForRequest($request);
        }
        unset($request);
        return $requests;
    }

    private static function syncProfileSelectionsForOrganisation(int $organisationId): void
    {
        if ($organisationId < 1) {
            return;
        }

        $roles = Role::findBySlugs(self::trainerRoleSlugs());
        $roleIds = array_values(array_filter(array_map(
            static fn(array $role): int => (int) ($role['id'] ?? 0),
            $roles
        )));
        if (!$roleIds) {
            return;
        }

        $organisationFieldKeys = [];
        foreach ($roleIds as $roleId) {
            foreach (Form::forRole($roleId, true, 'profile') as $form) {
                foreach (FormField::forForm((int) $form['id']) as $field) {
                    if (($field['field_type'] ?? '') === 'organisation') {
                        $organisationFieldKeys[] = (string) ($field['field_key'] ?? '');
                    }
                }
            }
        }
        $organisationFieldKeys = array_values(array_unique(array_filter($organisationFieldKeys)));
        if (!$organisationFieldKeys) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.id AS user_id, fr.answers
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             INNER JOIN form_responses fr ON fr.user_id = u.id
             WHERE u.is_active = 1
               AND r.id IN ($placeholders)
               AND fr.submitted_at IS NOT NULL"
        );
        $stmt->execute($roleIds);

        $insert = Database::connection()->prepare(
            "INSERT IGNORE INTO organisation_memberships (user_id, organisation_id, status)
             VALUES (?, ?, 'pending')"
        );
        foreach ($stmt->fetchAll() as $row) {
            $answers = $row['answers'] ?? null;
            if (is_string($answers) && $answers !== '') {
                $answers = json_decode($answers, true) ?: [];
            }
            if (!is_array($answers)) {
                continue;
            }
            foreach ($organisationFieldKeys as $key) {
                $raw = $answers[$key] ?? [];
                if (!is_array($raw)) {
                    $raw = $raw !== '' && $raw !== null ? [$raw] : [];
                }
                if (in_array($organisationId, array_map('intval', $raw), true)) {
                    $insert->execute([(int) $row['user_id'], $organisationId]);
                    break;
                }
            }
        }
    }

    private static function profileDetailsForRequest(array $request): array
    {
        $details = [];
        $seen = [];
        self::addProfileDetail($details, $seen, 'Name', userDisplayName($request));
        self::addProfileDetail($details, $seen, 'Email', (string) ($request['email'] ?? ''));
        self::addProfileDetail($details, $seen, 'Phone', (string) ($request['phone'] ?? ''));
        $forms = Form::forRole((int) ($request['role_id'] ?? 0), true, 'profile');
        foreach ($forms as $form) {
            $response = FormResponse::findForUserForm((int) $request['user_id'], (int) $form['id']);
            $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
            if (!$answers) {
                continue;
            }
            foreach (FormField::forForm((int) $form['id']) as $field) {
                if (FormFieldTypes::isLayout($field['field_type'] ?? '') || ($field['field_type'] ?? '') === 'organisation') {
                    continue;
                }
                $value = $answers[$field['field_key']] ?? '';
                $text = trim(FormField::formatAnswer($field, $value));
                self::addProfileDetail($details, $seen, (string) ($field['label'] ?? 'Detail'), $text);
                if (count($details) >= 8) {
                    return $details;
                }
            }
        }
        return $details;
    }

    private static function addProfileDetail(array &$details, array &$seen, string $label, string $value): void
    {
        $label = trim($label);
        $value = trim($value);
        if ($label === '' || $value === '' || $value === '—' || $value === '-' || strtolower($value) === 'not answered') {
            return;
        }
        $key = strtolower($label . ':' . $value);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $details[] = [
            'label' => $label,
            'value' => $value,
        ];
    }

    public static function approvedOrganisationIds(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT organisation_id FROM organisation_memberships WHERE user_id = ? AND status = 'approved'"
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function isApproved(int $userId, int $organisationId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM organisation_memberships
             WHERE user_id = ? AND organisation_id = ? AND status = 'approved'
             LIMIT 1"
        );
        $stmt->execute([$userId, $organisationId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function ensureApproved(int $userId, int $organisationId, ?int $reviewerUserId = null): void
    {
        if ($userId < 1 || $organisationId < 1) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, status FROM organisation_memberships WHERE user_id = ? AND organisation_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $organisationId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === self::STATUS_APPROVED) {
                return;
            }
            $pdo->prepare(
                "UPDATE organisation_memberships
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = ?
                 WHERE id = ?"
            )->execute([$reviewerUserId, (int) $existing['id']]);
            return;
        }

        $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status, reviewed_by_user_id, reviewed_at)
             VALUES (?, ?, 'approved', ?, NOW())"
        )->execute([$userId, $organisationId, $reviewerUserId]);
    }

    public static function approve(int $id, int $organisationId, int $reviewerUserId): bool
    {
        return self::approveWithCategories($id, $organisationId, $reviewerUserId, null);
    }

    /**
     * Approves a pending request. When $categoryIds is given (student
     * requests), the reviewer's final category selection is saved — this is
     * how an organisation admin or branch admin can deselect categories the
     * student asked for before approving.
     */
    public static function approveWithCategories(int $id, int $organisationId, int $reviewerUserId, ?array $categoryIds): bool
    {
        $row = self::find($id);
        if (!$row || (int) $row['organisation_id'] !== $organisationId || $row['status'] !== self::STATUS_PENDING) {
            return false;
        }

        if ($categoryIds !== null) {
            $categoryJson = $categoryIds ? json_encode(array_values(array_unique(array_map('intval', $categoryIds)))) : null;
            Database::connection()->prepare(
                "UPDATE organisation_memberships
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = ?, category_ids = ?
                 WHERE id = ?"
            )->execute([$reviewerUserId, $categoryJson, $id]);
        } else {
            Database::connection()->prepare(
                "UPDATE organisation_memberships
                 SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = ?
                 WHERE id = ?"
            )->execute([$reviewerUserId, $id]);
        }

        $user = User::find((int) $row['user_id']);
        if ($user && empty($user['organisation_id'])) {
            User::setOrganisationId((int) $user['id'], $organisationId);
        }

        return true;
    }

    public static function reject(int $id, int $organisationId, int $reviewerUserId): bool
    {
        $row = self::find($id);
        if (!$row || (int) $row['organisation_id'] !== $organisationId || $row['status'] !== self::STATUS_PENDING) {
            return false;
        }

        Database::connection()->prepare(
            "UPDATE organisation_memberships
             SET status = 'rejected', reviewed_at = NOW(), reviewed_by_user_id = ?
             WHERE id = ?"
        )->execute([$reviewerUserId, $id]);

        return true;
    }

    public static function syncFromProfileAnswers(int $userId, string $roleSlug, array $fields, array $answers): void
    {
        $orgIds = [];
        foreach ($fields as $field) {
            if (($field['field_type'] ?? '') !== 'organisation') {
                continue;
            }
            $raw = $answers[$field['field_key']] ?? [];
            if (!is_array($raw)) {
                $raw = $raw !== '' && $raw !== null ? [$raw] : [];
            }
            foreach ($raw as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $orgIds[$id] = $id;
                }
            }
        }
        $orgIds = array_values($orgIds);

        if (self::isTrainerRole($roleSlug)) {
            self::syncSelections($userId, $orgIds);
            return;
        }

        if (self::isStudentRole($roleSlug)) {
            self::syncStudentSelections($userId, $orgIds);
        }
    }

    private static function syncStudentSelections(int $userId, array $selectedOrgIds): void
    {
        $selected = [];
        foreach ($selectedOrgIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }

        $existing = self::forUser($userId);
        $pdo = Database::connection();

        foreach ($existing as $row) {
            $orgId = (int) $row['organisation_id'];
            if (!empty($selected[$orgId])) {
                if ($row['status'] !== self::STATUS_APPROVED) {
                    $pdo->prepare(
                        "UPDATE organisation_memberships
                         SET status = 'approved', reviewed_at = NOW(), reviewed_by_user_id = NULL
                         WHERE id = ?"
                    )->execute([(int) $row['id']]);
                }
                unset($selected[$orgId]);
                continue;
            }
            $pdo->prepare('DELETE FROM organisation_memberships WHERE id = ?')->execute([(int) $row['id']]);
        }

        $insert = $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status, reviewed_at)
             VALUES (?, ?, 'approved', NOW())"
        );
        foreach (array_keys($selected) as $orgId) {
            $insert->execute([$userId, $orgId]);
        }

        $primary = (int) ($selectedOrgIds[0] ?? 0);
        User::assignOrganisation($userId, $primary > 0 ? $primary : null);
    }

    private static function syncSelections(int $userId, array $selectedOrgIds): void
    {
        $selected = [];
        foreach ($selectedOrgIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }

        $existing = self::forUser($userId);
        $pdo = Database::connection();

        foreach ($existing as $row) {
            $orgId = (int) $row['organisation_id'];
            if (!empty($selected[$orgId])) {
                if ($row['status'] === self::STATUS_REJECTED) {
                    $pdo->prepare(
                        "UPDATE organisation_memberships
                         SET status = 'pending', reviewed_at = NULL, reviewed_by_user_id = NULL
                         WHERE id = ?"
                    )->execute([(int) $row['id']]);
                }
                unset($selected[$orgId]);
                continue;
            }

            if ($row['status'] === self::STATUS_PENDING) {
                $pdo->prepare('DELETE FROM organisation_memberships WHERE id = ?')->execute([(int) $row['id']]);
            }
        }

        $insert = $pdo->prepare(
            "INSERT INTO organisation_memberships (user_id, organisation_id, status)
             VALUES (?, ?, 'pending')"
        );
        foreach (array_keys($selected) as $orgId) {
            $insert->execute([$userId, $orgId]);
        }
    }
}
