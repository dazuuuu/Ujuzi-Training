<?php

namespace App\Models;

use App\Core\Database;

class CourseModule
{
    public static function durations(): array
    {
        return [10, 20, 30, 60];
    }

    public static function forCourse(int $courseId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$courseId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM course_modules WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function create(array $fields): int
    {
        $pdo = Database::connection();
        $sort = (int) ($fields['sort_order'] ?? self::nextSort((int) $fields['course_id']));
        $pdo->prepare(
            'INSERT INTO course_modules (course_id, title, description, summary, notes, duration_minutes, video_source, video_path, video_url, materials, quiz_questions, pass_percent, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int) $fields['course_id'],
            $fields['title'],
            $fields['description'] ?? null,
            $fields['summary'] ?? null,
            $fields['notes'] ?? null,
            (int) ($fields['duration_minutes'] ?? 10),
            $fields['video_source'] === 'youtube' ? 'youtube' : 'upload',
            $fields['video_path'] ?? null,
            $fields['video_url'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            json_encode(self::normalizeQuestions($fields['quiz_questions'] ?? [])),
            self::normalizePassPercent($fields['pass_percent'] ?? 80),
            $sort,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE course_modules
             SET title = ?, description = ?, summary = ?, notes = ?, duration_minutes = ?, video_source = ?, video_path = ?, video_url = ?, materials = ?, quiz_questions = ?, pass_percent = ?
             WHERE id = ?'
        )->execute([
            $fields['title'],
            $fields['description'] ?? null,
            $fields['summary'] ?? null,
            $fields['notes'] ?? null,
            (int) ($fields['duration_minutes'] ?? 10),
            $fields['video_source'] === 'youtube' ? 'youtube' : 'upload',
            $fields['video_path'] ?? null,
            $fields['video_url'] ?? null,
            !empty($fields['materials']) ? json_encode(array_values($fields['materials'])) : null,
            json_encode(self::normalizeQuestions($fields['quiz_questions'] ?? [])),
            self::normalizePassPercent($fields['pass_percent'] ?? 80),
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM course_modules WHERE id = ?')->execute([$id]);
    }

    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (preg_match('/(?:youtube\\.com\\/(?:watch\\?v=|embed\\/|shorts\\/)|youtu\\.be\\/)([A-Za-z0-9_-]{11})/', $url, $match)) {
            return $match[1];
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        return null;
    }

    public static function embedUrl(?string $url): ?string
    {
        $id = self::youtubeId($url);
        if (!$id) {
            return null;
        }
        return 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0&modestbranding=1&playsinline=1&fs=0';
    }

    public static function normalizeQuestions($raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $question = trim((string) ($row['question'] ?? $row['text'] ?? ''));
            $options = $row['options'] ?? [];
            if (!is_array($options)) {
                continue;
            }
            $cleaned = [];
            foreach ($options as $option) {
                $option = trim((string) $option);
                if ($option !== '') {
                    $cleaned[] = $option;
                }
            }
            if ($question === '' || count($cleaned) < 2) {
                continue;
            }
            $correct = (int) ($row['correct'] ?? 0);
            if ($correct < 0 || $correct >= count($cleaned)) {
                $correct = 0;
            }
            $out[] = [
                'question' => $question,
                'options' => $cleaned,
                'correct' => $correct,
            ];
        }
        return $out;
    }

    public static function normalizePassPercent($value): int
    {
        $percent = (int) $value;
        if ($percent < 80) {
            $percent = 80;
        }
        return min(100, $percent);
    }

    public static function grade(array $module, array $answers): array
    {
        $questions = $module['quiz_questions'] ?? [];
        $total = count($questions);
        if ($total < 1) {
            return ['score' => 100, 'passed' => true, 'correct' => 0, 'total' => 0];
        }
        $correctCount = 0;
        foreach ($questions as $index => $question) {
            if ((int) ($answers[$index] ?? -1) === (int) ($question['correct'] ?? -2)) {
                $correctCount++;
            }
        }
        $score = (int) round(($correctCount / $total) * 100);
        $pass = self::normalizePassPercent($module['pass_percent'] ?? 80);
        return [
            'score' => $score,
            'passed' => $score >= $pass,
            'correct' => $correctCount,
            'total' => $total,
        ];
    }

    public static function withUnlockState(array $modules, array $progressByModule): array
    {
        $previousPassed = true;
        $out = [];
        foreach ($modules as $index => $module) {
            $progress = $progressByModule[(int) $module['id']] ?? null;
            $hasQuiz = !empty($module['quiz_questions']);
            $passed = $progress && !empty($progress['passed']);
            if (!$hasQuiz) {
                $passed = true;
            }
            $module['is_unlocked'] = $index === 0 || $previousPassed;
            $module['is_passed'] = $passed;
            $module['progress'] = $progress;
            $module['has_quiz'] = $hasQuiz;
            $out[] = $module;
            $previousPassed = $module['is_unlocked'] && $passed;
        }
        return $out;
    }

    public static function hydrate(array $row): array
    {
        $materials = $row['materials'] ?? null;
        if (is_string($materials) && $materials !== '') {
            $row['materials'] = json_decode($materials, true) ?: [];
        } elseif (!is_array($materials)) {
            $row['materials'] = [];
        }
        $row['quiz_questions'] = self::normalizeQuestions($row['quiz_questions'] ?? []);
        $row['pass_percent'] = self::normalizePassPercent($row['pass_percent'] ?? 80);
        $row['youtube_id'] = self::youtubeId($row['video_url'] ?? null);
        $row['embed_url'] = self::embedUrl($row['video_url'] ?? null);
        $row['duration_minutes'] = (int) ($row['duration_minutes'] ?? 10);
        return $row;
    }

    private static function nextSort(int $courseId): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM course_modules WHERE course_id = ?');
        $stmt->execute([$courseId]);
        return (int) $stmt->fetchColumn();
    }
}
