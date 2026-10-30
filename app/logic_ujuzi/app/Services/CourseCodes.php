<?php

namespace App\Services;

use App\Core\Database;

/**
 * Course codes: the tutor types the code when creating a course (e.g.
 * CS101); it must be unique in the system. Super Admin may still give an
 * organisation a numeric range (e.g. 101–130) — its free codes are offered
 * as suggestions. Students can find a course by typing its code. Codes are
 * for finding courses only — they are never printed on certificates.
 */
class CourseCodes
{
    /** [from, to] for the organisation, or null when it has no range. */
    public static function rangeFor(int $organisationId): ?array
    {
        try {
            $stmt = Database::connection()->prepare('SELECT course_code_from, course_code_to FROM organisations WHERE id = ?');
            $stmt->execute([$organisationId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            return null; // before the course-codes update
        }
        return $row && $row['course_code_from'] !== null && $row['course_code_to'] !== null
            ? [(int) $row['course_code_from'], (int) $row['course_code_to']]
            : null;
    }

    /** Why this range can't be given to the organisation, or null when it can. */
    public static function rangeError(int $organisationId, ?int $from, ?int $to): ?string
    {
        if ($from === null && $to === null) {
            return null;
        }
        if ($from === null || $to === null || $from < 1 || $to < $from) {
            return 'Give the course code range as two numbers, the first not bigger than the second (e.g. 101 to 130).';
        }
        if ($to - $from > 9999) {
            return 'Keep a course code range under 10,000 codes.';
        }
        $stmt = Database::connection()->prepare(
            'SELECT name, course_code_from, course_code_to FROM organisations
             WHERE id <> ? AND course_code_from IS NOT NULL AND course_code_from <= ? AND course_code_to >= ? LIMIT 1'
        );
        $stmt->execute([$organisationId, $to, $from]);
        if ($clash = $stmt->fetch()) {
            return 'Codes ' . $from . '–' . $to . ' overlap ' . $clash['name'] . "'s range (" . $clash['course_code_from'] . '–' . $clash['course_code_to'] . '). Each organisation needs its own codes.';
        }
        return null;
    }

    public static function setRange(int $organisationId, ?int $from, ?int $to): void
    {
        try {
            Database::connection()->prepare('UPDATE organisations SET course_code_from = ?, course_code_to = ? WHERE id = ?')
                ->execute([$from, $to, $organisationId]);
        } catch (\Throwable $e) {
            // Before the course-codes update.
        }
    }

    /** Codes in the organisation's range no other course uses (plus this course's own). */
    public static function available(int $organisationId, ?int $courseId = null): array
    {
        $range = self::rangeFor($organisationId);
        if (!$range) {
            return [];
        }
        $stmt = Database::connection()->prepare('SELECT course_code FROM courses WHERE course_code BETWEEN ? AND ? AND id <> ?');
        $stmt->execute([$range[0], $range[1], (int) $courseId]);
        $used = array_flip(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)));
        $free = [];
        for ($code = $range[0]; $code <= $range[1]; $code++) {
            if (!isset($used[$code])) {
                $free[] = $code;
            }
        }
        return $free;
    }

    /** Normalises what the tutor typed: trimmed, upper-case, inner spaces removed. */
    public static function normalize(string $raw): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($raw)));
    }

    /**
     * Why this typed code can't be used for the course, or null when it can.
     * Tutors make up their own code (letters, digits, dashes); it must be
     * unique across the whole system.
     */
    public static function validate(string $code, ?int $courseId = null): ?string
    {
        if ($code === '') {
            return 'Type a course code for this course (e.g. CS101). Students use it to find the course.';
        }
        if (!preg_match('/^[A-Z0-9][A-Z0-9\-\/]{1,19}$/', $code)) {
            return 'A course code is 2–20 letters, numbers or dashes, with no spaces (e.g. CS101 or ICT-204).';
        }
        try {
            $stmt = Database::connection()->prepare('SELECT title FROM courses WHERE UPPER(course_code) = ? AND id <> ? LIMIT 1');
            $stmt->execute([$code, (int) $courseId]);
            if ($clash = $stmt->fetch()) {
                return 'Course code ' . $code . ' is already used by "' . $clash['title'] . '". Choose another code.';
            }
        } catch (\Throwable $e) {
            return null; // before the course-codes update
        }
        return null;
    }

    /** Gives the course this code (empty clears it). Returns why not, or null when done. */
    public static function assign(int $courseId, string $code): ?string
    {
        $code = self::normalize($code);
        if ($code !== '' && ($error = self::validate($code, $courseId)) !== null) {
            return $error;
        }
        try {
            Database::connection()->prepare('UPDATE courses SET course_code = ? WHERE id = ?')->execute([$code !== '' ? $code : null, $courseId]);
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }
}
