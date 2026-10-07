<?php

namespace App\Models;

use App\Core\Database;

class User
{
    /** Given to accounts an organisation admin (or Super Admin) creates directly, since they have no password of their own yet. */
    public const DEFAULT_PASSWORD = 'Ujuzi@2025';

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByIdentifier(string $type, string $value): ?array
    {
        if ($type === 'phone') {
            return self::findByPhone($value);
        }
        $column = 'LOWER(TRIM(u.email))';
        $value = strtolower(trim($value));
        $stmt = Database::connection()->prepare(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name, o.description AS organisation_description
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE $column = ? ORDER BY u.id ASC LIMIT 1"
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /**
     * The account for a phone number, however it was typed: 0714…, 714…,
     * +254714… and 254714… are the same number (compared on the last 9
     * digits). Falls back to the phone people answered on their profile form,
     * which is then saved to their account so the next lookup is direct.
     */
    public static function findByPhone(string $phone): ?array
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 9) {
            return null;
        }
        $tail = substr($digits, -9);
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE phone IS NOT NULL AND phone <> ''
               AND RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 9) = ? LIMIT 2"
        );
        $stmt->execute([$tail]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if (count($ids) === 1) {
            return self::find((int) $ids[0]);
        }
        if ($ids) {
            return null; // two accounts share it: too ambiguous to sign in with
        }
        try {
            $stmt = $pdo->prepare('SELECT DISTINCT user_id FROM form_responses WHERE answers LIKE ? LIMIT 20');
            $stmt->execute(['%' . $tail . '%']);
            $found = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $userId) {
                $user = self::find((int) $userId);
                $answered = $user ? preg_replace('/\D/', '', self::phoneFromProfile($user)) : '';
                if ($answered !== '' && substr($answered, -9) === $tail) {
                    $found[] = $user;
                }
            }
            if (count($found) === 1) {
                self::syncPhone((int) $found[0]['id'], self::phoneFromProfile($found[0]));
                return self::find((int) $found[0]['id']);
            }
        } catch (\Throwable $e) {
            // No profile answers to look in.
        }
        return null;
    }

    public static function all(?int $organisationId = null, ?int $roleId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                       r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id
                WHERE 1=1';
        $params = [];
        if ($organisationId) {
            $sql .= ' AND u.organisation_id = ?';
            $params[] = $organisationId;
        }
        if ($roleId) {
            $sql .= ' AND u.role_id = ?';
            $params[] = $roleId;
        }
        $sql .= ' ORDER BY u.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function allForOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE u.organisation_id = ?
                OR EXISTS (
                    SELECT 1 FROM organisation_memberships m
                    WHERE m.user_id = u.id
                      AND m.organisation_id = ?
                      AND m.status = \'approved\'
                )
             ORDER BY u.created_at DESC'
        );
        $stmt->execute([$organisationId, $organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function studentsForAttachmentProvider(int $providerUserId): array
    {
        $studentRole = Role::findBySlug('student');
        if (!$studentRole) {
            return [];
        }
        $students = array_filter(
            self::all(null, (int) $studentRole['id']),
            static fn(array $student): bool => (string) ($student['account_status'] ?? 'active') === 'active'
        );
        $forms = Form::forRole((int) $studentRole['id'], true, 'profile');
        $fields = [];
        foreach ($forms as $form) {
            foreach (FormField::forForm((int) $form['id']) as $field) {
                if (($field['field_type'] ?? '') === 'attachment_provider') {
                    $fields[] = $field['field_key'];
                }
            }
        }
        if (!$fields) {
            return [];
        }
        $matched = [];
        foreach ($students as $student) {
            foreach (FormResponse::forUser((int) $student['id']) as $response) {
                foreach ($fields as $key) {
                    $value = $response['answers'][$key] ?? '';
                    $values = is_array($value) ? $value : [$value];
                    if (in_array($providerUserId, array_map('intval', $values), true)) {
                        $matched[] = $student;
                        continue 3;
                    }
                }
            }
        }
        return $matched;
    }

    public static function attachmentTrainersForOrganisations(array $organisationIds): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds))));
        if (!$organisationIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.has_admin_features, r.is_under_organisation,
                    r.can_manage_users, r.managed_role_slugs, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE r.slug = 'attachment_trainer'
               AND u.is_active = 1
               AND (
                    u.organisation_id IN ($placeholders)
                    OR EXISTS (
                        SELECT 1 FROM organisation_memberships m
                        WHERE m.user_id = u.id
                          AND m.organisation_id IN ($placeholders)
                          AND m.status = 'approved'
                    )
               )
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute(array_merge($organisationIds, $organisationIds));
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * Every active organisation providing attachment. Attachment is open to all
     * students, so this is not filtered by course, category or organisation.
     * A provider with no branches is still listed — its own admin reviews.
     */
    public static function allAttachmentProviders(): array
    {
        $stmt = Database::connection()->query(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN organisations o ON o.id = u.organisation_id
             WHERE r.slug = 'attachment_trainer' AND u.is_active = 1
             ORDER BY o.name ASC, u.first_name ASC, u.last_name ASC"
        );
        $providers = [];
        foreach (array_map([self::class, 'hydrate'], $stmt->fetchAll()) as $provider) {
            $provider['attachment_branches'] = OrganisationBranch::forActiveAttachmentProviderIds([(int) $provider['id']]);
            $listing = self::attachmentListing($provider);
            $provider['listing_name'] = $listing['business_name'];
            $provider['listing_location'] = $listing['location'];
            $provider['listing_offered'] = $listing['attachment_offered'];
            $providers[] = $provider;
        }
        return $providers;
    }

    /** What an attachment provider listed about itself on its registration form (business name, location, what it offers). */
    private static function attachmentListing(array $provider): array
    {
        $listing = ['business_name' => '', 'location' => '', 'attachment_offered' => ''];
        try {
            foreach (Form::forRole((int) $provider['role_id'], true, 'profile') as $form) {
                $response = FormResponse::findForUserForm((int) $provider['id'], (int) $form['id']);
                $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
                foreach ($listing as $key => $value) {
                    if ($value === '' && isset($answers[$key]) && is_scalar($answers[$key])) {
                        $listing[$key] = trim((string) $answers[$key]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Listing details are optional — the provider is still shown by name.
        }
        return $listing;
    }

    public static function attachmentOptionsForOrganisations(array $organisationIds): array
    {
        $options = self::attachmentTrainersForOrganisations($organisationIds);
        foreach ($options as &$option) {
            $option['attachment_duration'] = self::attachmentDuration($option);
            $option['attachment_details'] = self::profileDetails($option, ['attachment_provider_branches', 'branch']);
            $option['attachment_branches'] = OrganisationBranch::forActiveAttachmentProviderIds([(int) $option['id']]);
            if (!$option['attachment_branches'] && !empty($option['organisation_id'])) {
                $option['attachment_branches'] = OrganisationBranch::forActiveOrganisationIds([(int) $option['organisation_id']]);
            }
        }
        unset($option);
        return $options;
    }

    /**
     * Attachment providers eligible for one specific course: matched to the
     * course's organisation, and — when a provider chose specific categories
     * to serve for that organisation — scoped to the course's category. A
     * provider with no category restriction for that organisation matches
     * every category (keeps existing/legacy provider accounts working).
     */
    public static function attachmentProvidersForCourse(array $course): array
    {
        $orgId = (int) ($course['organisation_id'] ?? 0);
        if ($orgId < 1) {
            return [];
        }
        $courseCategoryIds = \App\Models\Course::categoryIdsFor((int) ($course['id'] ?? 0));
        if (!$courseCategoryIds) {
            $courseCategoryIds = array_filter([(int) ($course['category_id'] ?? 0)]);
        }
        $providers = self::attachmentOptionsForOrganisations([$orgId]);
        $providers = array_values(array_filter($providers, static fn(array $p): bool => !empty($p['attachment_branches'])));

        return array_values(array_filter($providers, static function (array $provider) use ($orgId, $courseCategoryIds): bool {
            if ((int) ($provider['organisation_id'] ?? 0) === $orgId) {
                // Directly assigned to this organisation (no membership row) — no restriction.
                return true;
            }
            $categoryIds = \App\Models\OrganisationMembership::approvedCategoryIdsFor((int) $provider['id'], $orgId);
            return !$categoryIds || array_intersect($courseCategoryIds, $categoryIds);
        }));
    }

    /**
     * Most recent attachment provider/branch a student picked, read straight
     * from attachment_applications (the account/attachment-providers page is
     * the source of truth now, not the profile form).
     */
    public static function selectedAttachmentForStudent(int $studentUserId): array
    {
        $selection = ['provider_id' => 0, 'branch_id' => 0];
        $stmt = Database::connection()->prepare(
            'SELECT provider_user_id, branch_id FROM attachment_applications
             WHERE student_user_id = ? ORDER BY updated_at DESC LIMIT 1'
        );
        $stmt->execute([$studentUserId]);
        $row = $stmt->fetch();
        if ($row) {
            $selection['provider_id'] = (int) ($row['provider_user_id'] ?? 0);
            $selection['branch_id'] = (int) ($row['branch_id'] ?? 0);
        }
        return $selection;
    }

    public static function searchAttachmentProviders(string $query = '', int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $query = trim($query);
        $sql = "SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id
                WHERE r.slug = 'attachment_trainer' AND u.is_active = 1";
        $params = [];
        if ($query !== '') {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.name LIKE ?)";
            $like = '%' . $query . '%';
            $params = [$like, $like, $like, $like];
        }
        $sql .= ' ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC LIMIT ' . $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function validActiveAttachmentProviderIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT u.id
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1 AND r.slug = 'attachment_trainer' AND u.id IN ($placeholders)"
        );
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function namesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT id, first_name, last_name, email FROM users WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $label = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $names[(int) $row['id']] = $label !== '' ? $label : (string) ($row['email'] ?? ('User #' . $row['id']));
        }
        return $names;
    }

    public static function attachmentDuration(array $user): string
    {
        try {
            $forms = Form::forRole((int) $user['role_id'], true, 'profile');
        } catch (\Throwable $e) {
            return '';
        }
        foreach ($forms as $form) {
            $fields = FormField::forForm((int) $form['id']);
            $response = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
            $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
            foreach ($fields as $field) {
                if (($field['field_type'] ?? '') !== 'duration') {
                    continue;
                }
                $label = FormField::formatAnswer($field, $answers[$field['field_key']] ?? null);
                if ($label !== '' && $label !== '—') {
                    return $label;
                }
            }
        }
        return '';
    }

    public static function profileDetails(array $user, array $skipKeys = []): array
    {
        $details = [];
        $skipKeys = array_fill_keys($skipKeys, true);
        try {
            $forms = Form::forRole((int) $user['role_id'], true, 'profile');
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($forms as $form) {
            $fields = FormField::forForm((int) $form['id']);
            $response = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
            $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
            foreach ($fields as $field) {
                $key = (string) ($field['field_key'] ?? '');
                $type = (string) ($field['field_type'] ?? '');
                if ($key === '' || isset($skipKeys[$key]) || in_array($type, ['name', 'phone', 'email', 'duration', 'branches', 'branch_select'], true) || FormFieldTypes::isLayout($type)) {
                    continue;
                }
                $text = FormField::formatAnswer($field, $answers[$key] ?? null);
                if ($text === '' || $text === '—') {
                    continue;
                }
                $details[] = [
                    'label' => (string) ($field['label'] ?? 'Detail'),
                    'value' => $text,
                ];
            }
        }
        return $details;
    }

    public static function setOrganisationId(int $id, int $organisationId): void
    {
        Database::connection()
            ->prepare('UPDATE users SET organisation_id = ? WHERE id = ? AND organisation_id IS NULL')
            ->execute([$organisationId, $id]);
    }

    public static function assignOrganisation(int $id, ?int $organisationId): void
    {
        Database::connection()
            ->prepare('UPDATE users SET organisation_id = ? WHERE id = ?')
            ->execute([$organisationId && $organisationId > 0 ? $organisationId : null, $id]);
    }

    /**
     * Keeps the account phone in step with the phone the person typed on
     * their profile form (students sign up with email only). Skipped when
     * another account already uses that number.
     */
    public static function syncPhone(int $id, string $phone): void
    {
        $phone = self::normalizePhone($phone);
        if ($phone === '') {
            return;
        }
        try {
            if (!self::phoneTaken($phone, $id)) {
                Database::connection()->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$phone, $id]);
            }
        } catch (\Throwable $e) {
            // Leave the account phone as it was.
        }
    }

    /** The phone answered on the person's profile forms, or ''. */
    public static function phoneFromProfile(array $user): string
    {
        try {
            foreach (Form::forRole((int) $user['role_id'], true, 'profile') as $form) {
                $response = FormResponse::findForUserForm((int) $user['id'], (int) $form['id']);
                $answers = is_array($response['answers'] ?? null) ? $response['answers'] : [];
                foreach (FormField::forForm((int) $form['id']) as $field) {
                    if (($field['field_type'] ?? '') === 'phone' && is_scalar($answers[$field['field_key']] ?? null) && trim((string) $answers[$field['field_key']]) !== '') {
                        return trim((string) $answers[$field['field_key']]);
                    }
                }
            }
        } catch (\Throwable $e) {
            return '';
        }
        return '';
    }

    public static function updateProfileNames(int $id, string $firstName, string $lastName, ?string $otherNames = null): void
    {
        Database::connection()
            ->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE id = ?')
            ->execute([$firstName !== '' ? $firstName : null, $lastName !== '' ? $lastName : null, $id]);
        if ($otherNames !== null) {
            Database::connection()
                ->prepare('UPDATE users SET other_names = ? WHERE id = ?')
                ->execute([trim($otherNames) !== '' ? trim($otherNames) : null, $id]);
        }
    }

    public static function countByRole(): array
    {
        return Database::connection()->query(
            'SELECT r.slug, r.name, COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id, r.slug, r.name
             ORDER BY r.sort_order ASC'
        )->fetchAll();
    }

    public static function count(?int $organisationId = null): int
    {
        if ($organisationId) {
            $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE organisation_id = ?');
            $stmt->execute([$organisationId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function recent(int $limit = 8, ?int $organisationId = null): array
    {
        $sql = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, o.name AS organisation_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                LEFT JOIN organisations o ON o.id = u.organisation_id';
        $params = [];
        if ($organisationId) {
            $sql .= ' WHERE u.organisation_id = ?';
            $params[] = $organisationId;
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT ' . (int) $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    /** Whether another account already uses this email (capitals ignored). */
    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }
        $stmt = Database::connection()->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(email)) = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, (int) $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Whether another account already uses this phone number, however it is written (last 9 digits). */
    public static function phoneTaken(string $phone, ?int $exceptId = null): bool
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 9) {
            return false;
        }
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM users WHERE phone IS NOT NULL AND phone <> '' AND id <> ?
               AND RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), ')', ''), 9) = ? LIMIT 1"
        );
        $stmt->execute([(int) $exceptId, substr($digits, -9)]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Every email and phone number belongs to one account only — people sign
     * in with either. Throws when another account already uses them.
     */
    public static function assertUnique(?string $email, ?string $phone, ?int $exceptId = null): void
    {
        if ($email !== null && self::emailTaken($email, $exceptId)) {
            throw new DuplicateIdentifierException('The email ' . trim($email) . ' is already used by another account.');
        }
        if ($phone !== null && self::phoneTaken($phone, $exceptId)) {
            throw new DuplicateIdentifierException('The phone number ' . trim($phone) . ' is already used by another account.');
        }
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $email = strtolower(trim((string) ($fields['email'] ?? '')));
        $phone = trim((string) ($fields['phone'] ?? ''));
        self::assertUnique($email, $phone);
        $passwordHash = null;
        if (!empty($fields['password'])) {
            $passwordHash = password_hash((string) $fields['password'], PASSWORD_DEFAULT);
        } elseif (!empty($fields['password_hash'])) {
            $passwordHash = (string) $fields['password_hash'];
        }
        $pdo->prepare(
            'INSERT INTO users (role_id, organisation_id, email, password_hash, must_change_password, phone, first_name, last_name, other_names, is_active, dashboard_created_at, profile_created_at, email_verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $email !== '' ? $email : null,
            $passwordHash,
            !empty($fields['must_change_password']) ? 1 : 0,
            $phone !== '' ? $phone : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            trim((string) ($fields['other_names'] ?? '')) !== '' ? trim((string) $fields['other_names']) : null,
            isset($fields['is_active']) && !$fields['is_active'] ? 0 : 1,
            !empty($fields['email_verified_at']) ? date('Y-m-d H:i:s') : null,
        ]);
        $id = (int) $pdo->lastInsertId();
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
        self::assignRegistrationNumber($id);
        return $id;
    }

    /**
     * Gives a student their registration number, e.g. UJ0012709/26: UJ, their
     * place in the order students registered (001, 002 …), the day and month
     * they registered, then /year. Does nothing for other roles, for a student
     * who already has one, or before the registration-number migration ran.
     */
    public static function assignRegistrationNumber(int $userId): ?string
    {
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                "SELECT u.registration_number, u.created_at, r.slug FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.id = ?"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        } catch (\PDOException $e) {
            return null;
        }
        if (!$row || $row['slug'] !== 'student') {
            return null;
        }
        if (!empty($row['registration_number'])) {
            return (string) $row['registration_number'];
        }
        $at = strtotime((string) $row['created_at']) ?: time();
        // The unique key on registration_seq settles two sign-ups at the same moment: retry with the next number.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $seq = (int) $pdo->query('SELECT COALESCE(MAX(registration_seq), 0) + 1 FROM users')->fetchColumn();
            $number = 'UJ' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . date('d', $at) . date('m', $at) . '/' . date('y', $at);
            try {
                $pdo->prepare('UPDATE users SET registration_seq = ?, registration_number = ? WHERE id = ? AND registration_number IS NULL')
                    ->execute([$seq, $number, $userId]);
                return $number;
            } catch (\PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }
        return null;
    }

    public static function update(int $id, array $fields): void
    {
        self::assertUnique((string) ($fields['email'] ?? ''), (string) ($fields['phone'] ?? ''), $id);
        $status = (string) ($fields['account_status'] ?? (!empty($fields['is_active']) ? 'active' : 'blocked'));
        if (!in_array($status, ['active', 'blocked', 'suspended'], true)) {
            $status = !empty($fields['is_active']) ? 'active' : 'blocked';
        }
        Database::connection()->prepare(
            'UPDATE users SET role_id = ?, organisation_id = ?, email = ?, phone = ?, first_name = ?, last_name = ?, other_names = ?, is_active = ?, account_status = ? WHERE id = ?'
        )->execute([
            (int) $fields['role_id'],
            !empty($fields['organisation_id']) ? (int) $fields['organisation_id'] : null,
            $fields['email'] !== '' ? $fields['email'] : null,
            $fields['phone'] !== '' ? $fields['phone'] : null,
            $fields['first_name'] ?? null,
            $fields['last_name'] ?? null,
            trim((string) ($fields['other_names'] ?? '')) !== '' ? trim((string) $fields['other_names']) : null,
            !empty($fields['is_active']) ? 1 : 0,
            $status,
            $id,
        ]);
        FormResponse::provisionForUser($id, (int) $fields['role_id']);
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['active', 'blocked', 'suspended'], true)) {
            throw new \InvalidArgumentException('Invalid user status.');
        }
        Database::connection()->prepare(
            'UPDATE users SET account_status = ?, is_active = ? WHERE id = ?'
        )->execute([$status, $status === 'active' ? 1 : 0, $id]);
    }

    public static function updateCredentials(int $id, ?string $email, ?string $phone): void
    {
        self::assertUnique($email, $phone, $id);
        Database::connection()->prepare(
            'UPDATE users SET email = ?, phone = ? WHERE id = ?'
        )->execute([
            $email !== null && $email !== '' ? strtolower(trim($email)) : null,
            $phone !== null && $phone !== '' ? self::normalizePhone($phone) : null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        try {
            Database::connection()->prepare(
                "DELETE FROM organisation_branches WHERE owner_type = 'attachment_provider' AND owner_user_id = ?"
            )->execute([$id]);
        } catch (\Throwable $e) {
            // Branch ownership is added by the branch ownership migration.
        }
        Database::connection()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public static function markEmailVerified(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?')
            ->execute([$id]);
    }

    public static function touchLastLogin(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([$id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        Database::connection()
            ->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /** Sets a new password and clears the forced-change flag — used the first time someone logs in with an admin-assigned default password. */
    public static function completeForcedPasswordChange(int $id, string $password): void
    {
        Database::connection()
            ->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function setMustChangePassword(int $id, bool $mustChange): void
    {
        Database::connection()
            ->prepare('UPDATE users SET must_change_password = ? WHERE id = ?')
            ->execute([$mustChange ? 1 : 0, $id]);
    }

    /**
     * The account this email and password open. While duplicates exist
     * (locked, waiting to be fixed) several accounts can share an email, so
     * the password decides which one it is.
     */
    public static function verifyPassword(string $email, string $password): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, password_hash FROM users WHERE LOWER(TRIM(email)) = ?');
        $stmt->execute([strtolower(trim($email))]);
        foreach ($stmt->fetchAll() as $row) {
            if ((string) $row['password_hash'] !== '' && password_verify($password, (string) $row['password_hash'])) {
                return self::find((int) $row['id']);
            }
        }
        return null;
    }

    /** Locked: another account shares this account's email or phone, until the owner fixes it. */
    public static function isLocked(array $user): bool
    {
        return !empty($user['locked_at']);
    }

    public static function unlock(int $id): void
    {
        Database::connection()->prepare(
            'UPDATE users SET locked_at = NULL, lock_reason = NULL, pending_email = NULL, email_verify_token = NULL, email_verify_expires = NULL WHERE id = ?'
        )->execute([$id]);
    }

    /** Stores the new email waiting to be verified and returns the token for its link. */
    public static function startEmailChange(int $id, string $email, ?string $phone): string
    {
        $token = bin2hex(random_bytes(24));
        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET pending_email = ?, email_verify_token = ?, email_verify_expires = NOW() + INTERVAL 2 DAY WHERE id = ?')
            ->execute([strtolower(trim($email)), hash('sha256', $token), $id]);
        if ($phone !== null && trim($phone) !== '') {
            $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([self::normalizePhone($phone), $id]);
        }
        return $token;
    }

    /**
     * The verify button: makes the pending email the account's email and
     * unlocks it. Returns the account, or null for an unknown / expired link
     * or when the email was taken in the meantime.
     */
    public static function confirmEmailChange(string $token): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, pending_email FROM users WHERE email_verify_token = ? AND email_verify_expires > NOW() LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row || empty($row['pending_email']) || self::emailTaken((string) $row['pending_email'], (int) $row['id'])) {
            return null;
        }
        Database::connection()->prepare('UPDATE users SET email = ?, email_verified_at = NOW() WHERE id = ?')
            ->execute([$row['pending_email'], (int) $row['id']]);
        self::unlock((int) $row['id']);
        return self::find((int) $row['id']);
    }

    public static function passwordError(string $password, string $confirm = ''): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if ($confirm !== '' && $password !== $confirm) {
            return 'Password confirmation does not match.';
        }
        return null;
    }

    public const PUBLIC_PROFILE_FIELDS = ['photo_path', 'headline', 'bio', 'experience', 'linkedin_url', 'social_url'];

    /** Saves what students see about a tutor. */
    public static function updatePublicProfile(int $id, array $fields, string $phone): void
    {
        $values = [];
        foreach (self::PUBLIC_PROFILE_FIELDS as $key) {
            $value = trim((string) ($fields[$key] ?? ''));
            $values[] = $value !== '' ? $value : null;
        }
        self::assertUnique(null, $phone, $id);
        $values[] = $phone;
        $values[] = $id;
        Database::connection()->prepare(
            'UPDATE users SET photo_path = ?, headline = ?, bio = ?, experience = ?, linkedin_url = ?, social_url = ?, phone = ? WHERE id = ?'
        )->execute($values);
    }

    public static function setPhoto(int $id, ?string $path): void
    {
        Database::connection()->prepare('UPDATE users SET photo_path = ? WHERE id = ?')->execute([$path, $id]);
    }

    /** What a tutor must fill in before creating courses: photo, phone, about and experience. */
    public static function missingPublicProfile(array $user): array
    {
        $missing = [];
        foreach (['photo_path' => 'profile picture', 'phone' => 'phone number', 'bio' => 'about you', 'experience' => 'experience'] as $key => $label) {
            if (trim((string) ($user[$key] ?? '')) === '') {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    public static function hydrate(array $row): array
    {
        $managed = $row['managed_role_slugs'] ?? '[]';
        if (is_string($managed)) {
            $managed = json_decode($managed, true) ?: [];
        }
        $row['managed_role_slugs'] = array_values(array_filter((array) $managed));
        $row['has_admin_features'] = !empty($row['has_admin_features']);
        $row['is_under_organisation'] = !empty($row['is_under_organisation']);
        $row['can_manage_users'] = !empty($row['can_manage_users']);
        $row['has_password'] = !empty($row['password_hash']);
        $row['account_status'] = (string) ($row['account_status'] ?? (!empty($row['is_active']) ? 'active' : 'blocked'));
        unset($row['password_hash']);
        return $row;
    }
}
