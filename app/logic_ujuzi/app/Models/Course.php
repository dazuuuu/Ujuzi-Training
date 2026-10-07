<?php

namespace App\Models;

use App\Core\Database;

class Course
{
    public const VISIBILITY_STRICT = 'strict';
    public const VISIBILITY_GLOBAL = 'global';

    /**
     * " AND c.approval_status = 'approved'" — students only ever see courses
     * Super Admin approved. Empty before the approval update has run.
     */
    public static function approvedSql(string $alias = 'c'): string
    {
        static $ready = null;
        if ($ready === null) {
            try {
                $ready = (bool) Database::connection()->query("SHOW COLUMNS FROM courses LIKE 'approval_status'")->fetch();
            } catch (\Throwable $e) {
                $ready = false;
            }
        }
        return $ready ? " AND $alias.approval_status = 'approved'" : '';
    }

    /** Courses waiting for Super Admin, oldest first. */
    public static function pendingApproval(): array
    {
        if (self::approvedSql() === '') {
            return [];
        }
        return Database::connection()->query(
            "SELECT c.*, o.name AS organisation_name, u.first_name, u.last_name, u.email
             FROM courses c INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.approval_status = 'pending' ORDER BY c.created_at ASC"
        )->fetchAll();
    }

    public static function setApproval(int $id, string $status, ?string $note): void
    {
        Database::connection()->prepare(
            'UPDATE courses SET approval_status = ?, approval_note = ?, approved_at = IF(? = \'approved\', NOW(), NULL) WHERE id = ?'
        )->execute([$status, $note, $status, $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, cat.name AS category_name, cat.is_open AS category_is_open, o.name AS organisation_name,
                    u.first_name, u.last_name, u.email, u.photo_path AS tutor_photo, u.headline AS tutor_headline
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
                    u.first_name, u.last_name, u.email, u.photo_path AS tutor_photo, u.headline AS tutor_headline
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
                    u.first_name, u.last_name, u.email, u.photo_path AS tutor_photo, u.headline AS tutor_headline
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
    /**
     * Published courses a student can see: every general (global) course, and
     * every course of the organisations that approved them. A new course from
     * their organisation shows up by itself — it isn't limited to the
     * categories the student picked. Newest or most recently updated first.
     * $approvedCategoryIds is kept for callers but no longer narrows the list.
     */
    public static function forLearner(array $organisationIds, array $approvedCategoryIds = []): array
    {
        $organisationIds = array_values(array_unique(array_filter(array_map('intval', $organisationIds))));
        $sql = 'SELECT c.*, cat.name AS category_name, o.name AS organisation_name,
                       u.first_name, u.last_name, u.email, u.photo_path AS tutor_photo, u.headline AS tutor_headline
                FROM courses c
                INNER JOIN organisation_categories cat ON cat.id = c.category_id
                INNER JOIN organisations o ON o.id = c.organisation_id
                INNER JOIN users u ON u.id = c.trainer_user_id
                WHERE c.is_published = 1' . self::approvedSql() . ' AND (c.visibility = \'global\'';
        $params = [];
        if ($organisationIds) {
            $placeholders = implode(',', array_fill(0, count($organisationIds), '?'));
            $sql .= " OR (c.visibility = 'strict' AND c.organisation_id IN ($placeholders))";
            $params = $organisationIds;
        }
        $sql .= ') ORDER BY c.visibility DESC, COALESCE(c.updated_at, c.created_at) DESC, c.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    /** @return array<int, int> course id => number of students enrolled */
    public static function enrolmentCounts(array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds))));
        if (!$courseIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT course_id, COUNT(*) FROM course_enrollments WHERE course_id IN ($placeholders) GROUP BY course_id"
        );
        $stmt->execute($courseIds);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_KEY_PAIR));
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
                    u.first_name, u.last_name, u.email, u.photo_path AS tutor_photo, u.headline AS tutor_headline
             FROM courses c
             INNER JOIN organisation_categories cat ON cat.id = c.category_id
             INNER JOIN organisations o ON o.id = c.organisation_id
             INNER JOIN users u ON u.id = c.trainer_user_id
             WHERE c.is_published = 1' . self::approvedSql() . ' AND c.visibility = \'global\'
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

    /**
     * Whether this student has earned this course's certificate: the course
     * gives one, the student completed it, and (when the course needs
     * attachment) their attachment for it is completed.
     */
    public static function isCertifiableFor(int $userId, array $course): bool
    {
        if (empty($course['certificate_enabled']) || !self::isCompletedByUser((int) $course['id'], $userId)) {
            return false;
        }
        if (empty($course['requires_attachment'])) {
            return true;
        }
        try {
            return in_array((int) $course['id'], AttachmentApplication::certifiableCourseIdsForStudent($userId), true)
                || AttachmentApplication::hasCompletedAttachment($userId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** A course only counts as completed once its fee is cleared — nothing may still be owed. */
    public static function isCompletedByUser(int $courseId, int $userId): bool
    {
        // A finished run stays finished, even after a retake or the course resetting.
        if (\App\Services\CourseRetake::hasCompletion($userId, $courseId)) {
            return true;
        }
        $done = self::feeSettledByUser($courseId, $userId)
            && self::modulesCompletedByUser($courseId, $userId)
            && self::finalExamPassedByUser($courseId, $userId);
        if ($done) {
            \App\Services\CourseRetake::recordCompletion($userId, $courseId);
        }
        return $done;
    }

    public static function feeSettledByUser(int $courseId, int $userId): bool
    {
        try {
            return CourseEnrollment::isSettled($userId, $courseId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function modulesCompletedByUser(int $courseId, int $userId): bool
    {
        $modules = CourseModule::forCourse($courseId);
        if (!$modules) {
            return true; // nothing to work through; the student finishes with "Complete course"
        }
        try {
            $progress = CourseModuleProgress::forUserCourse($userId, $courseId);
        } catch (\Throwable $e) {
            $progress = [];
        }
        $state = CourseModule::withUnlockState($modules, $progress);
        foreach ($state as $module) {
            // Every module worked through (quiz taken pass or fail, or marked done).
            if (empty($module['is_unlocked']) || empty($module['is_done'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * The course's last step is done: its final exam passed, or — for a
     * course without a final exam — the student ticked "Complete course"
     * (recorded the same way, as a passed attempt).
     */
    public static function finalExamPassedByUser(int $courseId, int $userId): bool
    {
        $course = self::find($courseId);
        if (!$course) {
            return false;
        }
        try {
            $progress = CourseFinalExamProgress::findForUserCourse($userId, $courseId);
        } catch (\Throwable $e) {
            $progress = null;
        }
        return !empty($progress['passed']);
    }

    /** The course names as printed on certificates: just the title, nothing added. */
    public static function skillNames(array $courses): array
    {
        $skills = [];
        foreach ($courses as $course) {
            $label = trim((string) ($course['title'] ?? ''));
            if ($label !== '' && !in_array($label, $skills, true)) {
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

    /** The final exam's question bank and how many questions each student's paper has (null = all). */
    public static function updateFinalExam(int $id, array $questions, int $passPercent = 100, ?int $paperSize = null): void
    {
        Database::connection()->prepare(
            'UPDATE courses SET final_exam_questions = ?, final_pass_percent = ? WHERE id = ?'
        )->execute([json_encode(CourseModule::normalizeQuestions($questions)), self::FINAL_PASS_PERCENT, $id]);
        try {
            Database::connection()->prepare('UPDATE courses SET final_paper_size = ? WHERE id = ?')
                ->execute([$paperSize && $paperSize > 0 ? $paperSize : null, $id]);
        } catch (\Throwable $e) {
            // Before the exam-papers update: everyone gets every question.
        }
    }

    /** The certificate needs a perfect final exam. */
    public const FINAL_PASS_PERCENT = 100;

    /**
     * This student's final exam paper: a random selection from the question
     * bank (final_paper_size questions), in random order, with each question's
     * answer choices shuffled. The same student sees the same paper until
     * they sit it; each new attempt draws a new one.
     * @return array<int, array{q: int, opts: int[]}> paper position => original question index and option order
     */
    public static function finalPaper(array $course, int $userId, ?array $progress): array
    {
        $bank = $course['final_exam_questions'] ?? [];
        if (!$bank) {
            return [];
        }
        mt_srand(crc32($userId . ':' . (int) $course['id'] . ':' . ($progress['updated_at'] ?? $progress['id'] ?? 'first')));
        $order = array_keys($bank);
        shuffle($order);
        $size = (int) ($course['final_paper_size'] ?? 0);
        if ($size > 0) {
            $order = array_slice($order, 0, min($size, count($order)));
        }
        $paper = [];
        foreach ($order as $q) {
            $opts = array_keys($bank[$q]['options'] ?? []);
            shuffle($opts);
            $paper[] = ['q' => $q, 'opts' => $opts];
        }
        mt_srand(); // back to normal randomness for everything else
        return $paper;
    }

    /** The paper's questions as the student sees them (choices in their shuffled order). */
    public static function paperQuestions(array $course, array $paper): array
    {
        $bank = $course['final_exam_questions'] ?? [];
        $out = [];
        foreach ($paper as $item) {
            $question = $bank[$item['q']];
            $question['options'] = array_map(static fn(int $o) => $bank[$item['q']]['options'][$o], $item['opts']);
            $out[] = $question;
        }
        return $out;
    }

    /**
     * Marks a student's paper: their answers (by paper position and shown
     * choice) are turned back into the bank's questions and choices, then
     * marked. Only 100% passes.
     */
    public static function gradeFinalExam(array $course, array $answers, array $paper = []): array
    {
        $bank = $course['final_exam_questions'] ?? [];
        if (!$paper) {
            $paper = array_map(static fn(int $q): array => ['q' => $q, 'opts' => array_keys($bank[$q]['options'] ?? [])], array_keys($bank));
        }
        $questions = [];
        $original = [];
        foreach ($paper as $pos => $item) {
            $questions[] = $bank[$item['q']];
            $given = $answers[$pos] ?? null;
            if (is_array($given)) {
                $given = array_map(static fn($shown) => $item['opts'][(int) $shown] ?? -1, $given);
            } elseif (($bank[$item['q']]['type'] ?? '') !== 'text' && $given !== null && $given !== '') {
                $given = $item['opts'][(int) $given] ?? -1;
            }
            $original[] = $given;
        }
        return CourseModule::grade(['quiz_questions' => $questions, 'pass_percent' => self::FINAL_PASS_PERCENT], $original);
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
