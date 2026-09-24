<?php

namespace App\Models;

use App\Core\Database;

class Course
{
    public const VISIBILITY_STRICT = 'strict';
    public const VISIBILITY_GLOBAL = 'global';

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, cat.is_open AS category_is_open, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function forTrainer(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.trainer_user_id = ?
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([$userId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function studentsForTrainer(int $trainerUserId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT u.*, r.slug AS role_slug, r.name AS role_name,
                    o.name AS organisation_name, GROUP_CONCAT(DISTINCT c.title ORDER BY c.title SEPARATOR ', ') AS enrolled_courses
             FROM course_enrollments e
             INNER JOIN courses c ON c.id = e.course_id AND c.trainer_user_id = ?
             INNER JOIN users u ON u.id = e.user_id
             INNER JOIN roles r ON r.id = u.role_id AND r.slug = 'student'
             LEFT JOIN organisations o ON o.id = u.organisation_id
             GROUP BY u.id
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute([$trainerUserId]);
        return array_map([User::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function forOrganisation(int $organisationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.organisation_id = ?
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([$organisationId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * @param int[] $organisationIds Organisations the student is approved under.
     * @param int[] $approvedCategoryIds Categories the student is approved for, across those organisations.
     *   A strict-visibility course in a non-open category only shows if its category is in this list.
     */
    public static function forLearner(array $organisationIds, array $approvedCategoryIds = []): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds))));
        $approvedCategoryIds = array_values(array_unique(array_filter(array_map('intval', $approvedCategoryIds))));
        $sql = 'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                       u.first_name, u.last_name, u.email
                FROM courses c
                INNER JOIN organisation_categories cat ON cat.id = c.category_id
                INNER JOIN organisations o ON o.id = c.organisation_id
                INNER JOIN users u ON u.id = c.trainer_user_id
                WHERE c.is_published = 1 AND (c.visibility = \'global\'';
        $params = [];
        if ($organisationIds) {
            $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
            // Matches if ANY of the course's categories (not just its primary
            // display category) is open, or one the student is approved for.
            $sql .= " OR (c.visibility = 'strict' AND c.organisation_id IN ($placeholders) AND EXISTS (
                        SELECT 1 FROM course_categories cc
                        INNER JOIN organisation_categories oc ON oc.id = cc.category_id
                        WHERE cc.course_id = c.id AND (oc.is_open = 1";
            $params = $organisationIds;
            if ($approvedCategoryIds) {
                $catPlaceholders = implode(',', array_fill(0, count($approvedCategoryIds), '?'));
                $sql .= " OR oc.id IN ($catPlaceholders)";
                $params = array_merge($params, $approvedCategoryIds);
            }
            $sql .= ')))';
        }
        $sql .= ') ORDER BY c.visibility DESC, c.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function categoryIdsFor(int $courseId): array
    {
        $stmt = Database::connection()->prepare('SELECT category_id FROM course_categories WHERE course_id = ?');
        $stmt->execute([$courseId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function syncCategories(int $courseId, array $categoryIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM course_categories WHERE course_id = ?')->execute([$courseId]);
        $insert = $pdo->prepare('INSERT IGNORE INTO course_categories (course_id, category_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $categoryIds)) as $categoryId) {
            if ($categoryId > 0) {
                $insert->execute([$courseId, $categoryId]);
            }
        }
    }

    public static function publicListing(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.is_published = 1 AND c.visibility = \'global\'
             ORDER BY c.created_at DESC'
        );
        $stmt->execute();
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function completedByLearner(array $user): array
    {
        $courses = [];
        try {
            $courses = self::forLearner(\App\Core\Authz::learnerOrganisationIds($user), \App\Core\Authz::approvedCategoryIds($user));
        } catch (\Throwable $e) {
            return [];
        }
        $completed = [];
        foreach ($courses as $course) {
            if (self::isCompletedByUser((int) $course['id'], (int) $user['id'])) {
                $completed[] = $course;
            }
        }
        return $completed;
    }

    public static function certifiableCompletedByLearner(array $user): array
    {
        $completed = self::completedByLearner($user);
        if (!$completed) {
            return [];
        }
        $recommended = [];
        try {
            $recommended = AttachmentApplication::certifiableCourseIdsForStudent((int) $user['id']);
        } catch (\Throwable $e) {
            $recommended = [];
        }
        $recommendedLookup = array_fill_keys($recommended, true);
        // Attachment is no longer tied to a course: a completed attachment counts for every course.
        $attachmentDone = false;
        try {
            $attachmentDone = AttachmentApplication::hasCompletedAttachment((int) $user['id']);
        } catch (\Throwable $e) {
            $attachmentDone = false;
        }
        return array_values(array_filter($completed, static function (array $course) use ($recommendedLookup, $attachmentDone): bool {
            if (empty($course['certificate_enabled'])) {
                return false;
            }
            if (empty($course['requires_attachment'])) {
                return true;
            }
            return $attachmentDone || !empty($recommendedLookup[(int) $course['id']]);
        }));
    }

    public static function isCompletedByUser(int $courseId, int $userId): bool
    {
        return self::modulesCompletedByUser($courseId, $userId) && self::finalExamPassedByUser($courseId, $userId);
    }

    public static function modulesCompletedByUser(int $courseId, int $userId): bool
    {
        $modules = CourseModule::forCourse($courseId);
        if (!$modules) {
            return false;
        }
        try {
            $progress = CourseModuleProgress::forUserCourse($userId, $courseId);
        } catch (\Throwable $e) {
            $progress = [];
        }
        $state = CourseModule::withUnlockState($modules, $progress);
        foreach ($state as $module) {
            if (empty($module['is_unlocked']) || empty($module['is_passed'])) {
                return false;
            }
        }
        return true;
    }

    public static function finalExamPassedByUser(int $courseId, int $userId): bool
    {
        $course = self::find($courseId);
        if (!$course || empty($course['final_exam_questions'])) {
            return false;
        }
        try {
            $progress = CourseFinalExamProgress::findForUserCourse($userId, $courseId);
        } catch (\Throwable $e) {
            $progress = null;
        }
        return !empty($progress['passed']);
    }

    public static function skillNames(array $courses): array
    {
        $skills = [];
        foreach ($courses as $course) {
            $skill = trim((string) ($course['title'] ?? ''));
            $category = trim((string) ($course['category_name'] ?? ''));
            $label = $skill !== '' ? $skill : $category;
            if ($label === '') {
                continue;
            }
            if ($category !== '' && strcasecmp($skill, $category) !== 0) {
                $label = $skill . ' (' . $category . ')';
            }
            if (!in_array($label, $skills, true)) {
                $skills[] = $label;
            }
        }
        return $skills;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO courses (organisation_id, category_id, trainer_user_id, form_id, title, description, cover_image, materials, answers, is_published, visibility, enrollment_fee_ksh, requires_attachment, certificate_enabled, requires_full_registration, introduction_title, introduction_description, introduction_video_source, introduction_video_path, introduction_video_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['organisation_id'],
            (int) $fields['category_id'],
            (int) $fields['trainer_user_id'],
            !empty($fields['form_id']) ? (int) $fields['form_id'] : null,
            $fields['title'],
            $fields['description'] ?? null,
            $fields['cover_image'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            isset($fields['answers']) ? json_encode($fields['answers']) : null,
            isset($fields['is_published']) && !$fields['is_published'] ? 0 : 1,
            self::normalizeVisibility($fields['visibility'] ?? null),
            max(0, (float) ($fields['enrollment_fee_ksh'] ?? 0)),
            !empty($fields['requires_attachment']) ? 1 : 0,
            !empty($fields['certificate_enabled']) ? 1 : 0,
            !empty($fields['requires_full_registration']) ? 1 : 0,
            $fields['introduction_title'] ?? null,
            $fields['introduction_description'] ?? null,
            ($fields['introduction_video_source'] ?? '') === 'upload' ? 'upload' : 'youtube',
            $fields['introduction_video_path'] ?? null,
            $fields['introduction_video_url'] ?? null,
        ]);
        $id = (int) $pdo->lastInsertId();
        if (isset($fields['category_ids'])) {
            self::syncCategories($id, $fields['category_ids']);
        }
        return $id;
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE courses
             SET organisation_id = ?, category_id = ?, title = ?, description = ?, cover_image = ?, materials = ?, answers = ?, is_published = ?, visibility = ?, enrollment_fee_ksh = ?, requires_attachment = ?, certificate_enabled = ?, requires_full_registration = ?, introduction_title = ?, introduction_description = ?, introduction_video_source = ?, introduction_video_path = ?, introduction_video_url = ?
             WHERE id = ?'
        )->execute([
            (int) $fields['organisation_id'],
            (int) $fields['category_id'],
            $fields['title'],
            $fields['description'] ?? null,
            $fields['cover_image'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            isset($fields['answers']) ? json_encode($fields['answers']) : null,
            !empty($fields['is_published']) ? 1 : 0,
            self::normalizeVisibility($fields['visibility'] ?? null),
            max(0, (float) ($fields['enrollment_fee_ksh'] ?? 0)),
            !empty($fields['requires_attachment']) ? 1 : 0,
            !empty($fields['certificate_enabled']) ? 1 : 0,
            !empty($fields['requires_full_registration']) ? 1 : 0,
            $fields['introduction_title'] ?? null,
            $fields['introduction_description'] ?? null,
            ($fields['introduction_video_source'] ?? '') === 'upload' ? 'upload' : 'youtube',
            $fields['introduction_video_path'] ?? null,
            $fields['introduction_video_url'] ?? null,
            $id,
        ]);
        if (isset($fields['category_ids'])) {
            self::syncCategories($id, $fields['category_ids']);
        }
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
    }

    public static function updateFinalExam(int $id, array $questions, int $passPercent = 80): void
    {
        Database::connection()->prepare(
            'UPDATE courses SET final_exam_questions = ?, final_pass_percent = ? WHERE id = ?'
        )->execute([
            json_encode(CourseModule::normalizeQuestions($questions)),
            CourseModule::normalizePassPercent($passPercent),
            $id,
        ]);
    }

    public static function gradeFinalExam(array $course, array $answers): array
    {
        $module = [
            'quiz_questions' => $course['final_exam_questions'] ?? [],
            'pass_percent' => $course['final_pass_percent'] ?? 80,
        ];
        return CourseModule::grade($module, $answers);
    }

    public static function normalizeVisibility(?string $value): string
    {
        return $value === self::VISIBILITY_GLOBAL ? self::VISIBILITY_GLOBAL : self::VISIBILITY_STRICT;
    }

    public static function isGlobal(array $course): bool
    {
        return ($course['visibility'] ?? self::VISIBILITY_STRICT) === self::VISIBILITY_GLOBAL;
    }

    public static function hydrate(array $row): array
    {
        foreach (['materials', 'answers', 'final_exam_questions'] as $key) {
            $value = $row[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $row[$key] = json_decode($value, true) ?: [];
            } elseif (!is_array($value)) {
                $row[$key] = [];
            }
        }
        $row['final_exam_questions'] = CourseModule::normalizeQuestions($row['final_exam_questions'] ?? []);
        $row['final_pass_percent'] = CourseModule::normalizePassPercent($row['final_pass_percent'] ?? 80);
        $row['is_published'] = !empty($row['is_published']);
        $row['visibility'] = self::normalizeVisibility($row['visibility'] ?? null);
        $row['enrollment_fee_ksh'] = (float) ($row['enrollment_fee_ksh'] ?? 0);
        $row['requires_attachment'] = !empty($row['requires_attachment']);
        $row['certificate_enabled'] = !array_key_exists('certificate_enabled', $row) || !empty($row['certificate_enabled']);
        $row['requires_full_registration'] = !empty($row['requires_full_registration']);
        $row['introduction_video_source'] = ($row['introduction_video_source'] ?? '') === 'upload' ? 'upload' : 'youtube';
        $row['introduction_embed_url'] = CourseModule::embedUrl($row['introduction_video_url'] ?? null);
        return $row;
    }
}
