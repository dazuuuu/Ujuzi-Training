<?php

namespace App\Models;

use App\Core\Database;

class CourseModule
{
    public static function durations(): array
    {
        return [10, 20, 30, 60, 120, 150];
    }

    public static function normalizeDuration($value): int
    {
        if (is_numeric($value)) {
            $rawNumeric = trim((string) $value);
            $minutes = str_contains($rawNumeric, '.') ? ((float) $value * 60) : (float) $value;
            return max(1, min(1440, (int) round($minutes)));
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return 10;
        }
        $raw = str_replace(['hours', 'hour', 'hrs', 'hr'], 'h', $raw);
        $raw = str_replace(['minutes', 'minute', 'mins', 'min'], 'm', $raw);

        $hours = 0.0;
        $minutes = 0.0;
        if (preg_match('/(\d+(?:\.\d+)?)\s*h/', $raw, $match)) {
            $hours += (float) $match[1];
        }
        if (preg_match('/(\d+)\s+(\d+)\/(\d+)\s*h/', $raw, $match) && (int) $match[3] > 0) {
            $hours = (float) $match[1] + ((float) $match[2] / (float) $match[3]);
        } elseif (preg_match('/(\d+)\/(\d+)\s*h/', $raw, $match) && (int) $match[2] > 0) {
            $hours += (float) $match[1] / (float) $match[2];
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*m/', $raw, $match)) {
            $minutes += (float) $match[1];
        }
        if ($hours > 0 || $minutes > 0) {
            return max(1, min(1440, (int) round(($hours * 60) + $minutes)));
        }
        return max(1, min(1440, (int) round((float) $raw)));
    }

    public static function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        if ($remaining === 0) {
            return $hours . ' hr' . ($hours === 1 ? '' : 's');
        }
        return $hours . ' hr ' . $remaining . ' min';
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
            self::normalizeDuration($fields['duration_minutes'] ?? 10),
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
            self::normalizeDuration($fields['duration_minutes'] ?? 10),
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
            $type = in_array(($row['type'] ?? 'single_choice'), ['single_choice', 'multiple_choice', 'text'], true)
                ? (string) ($row['type'] ?? 'single_choice')
                : 'single_choice';
            $options = is_array($row['options'] ?? null) ? $row['options'] : [];
            $cleaned = [];
            foreach ($options as $option) {
                $option = trim((string) $option);
                if ($option !== '') {
                    $cleaned[] = $option;
                }
            }
            $accepted = $row['accepted_answers'] ?? [];
            if (is_string($accepted)) {
                $accepted = preg_split('/\r\n|\r|\n/', $accepted) ?: [];
            }
            if (!is_array($accepted)) {
                $accepted = [];
            }
            $accepted = array_values(array_filter(array_map(
                static fn($answer): string => trim((string) $answer),
                $accepted
            ), static fn(string $answer): bool => $answer !== ''));

            if ($question === '') {
                continue;
            }
            if ($type !== 'text' && count($cleaned) < 2) {
                continue;
            }
            $correct = $row['correct'] ?? 0;
            if ($type === 'multiple_choice') {
                $correct = is_array($correct) ? array_map('intval', $correct) : [$correct];
                $correct = array_values(array_filter(array_unique($correct), static fn(int $index): bool => $index >= 0 && $index < count($cleaned)));
                if (!$correct) {
                    $correct = [0];
                }
            } else {
                $correct = (int) $correct;
                if ($correct < 0 || $correct >= count($cleaned)) {
                    $correct = 0;
                }
            }
            $out[] = [
                'question' => $question,
                'type' => $type,
                'options' => $cleaned,
                'correct' => $correct,
                'accepted_answers' => $accepted,
            ];
        }
        return $out;
    }

    public static function normalizePassPercent($value): int
    {
        if ($value === null || $value === '') {
            return 80;
        }
        // The tutor decides the pass mark; only keep it within 1-100.
        return max(1, min(100, (int) $value));
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
            if (self::answerIsCorrect($question, $answers[$index] ?? null)) {
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

    /**
     * Adds is_unlocked / is_passed / is_done to each module. A module is done
     * once its quiz is passed (at the pass mark) or, without a quiz, ticked
     * "Mark as done". The next module opens only when the one before it is
     * done; paying for it is checked separately.
     */
    public static function withUnlockState(array $modules, array $progressByModule): array
    {
        $previousDone = true;
        $out = [];
        foreach (array_values($modules) as $index => $module) {
            $progress = $progressByModule[(int) $module['id']] ?? null;
            $module['is_unlocked'] = $index === 0 || $previousDone;
            $module['is_passed'] = $progress && !empty($progress['passed']);
            $module['is_done'] = $progress !== null && !empty($progress['passed']);
            $module['progress'] = $progress;
            $module['has_quiz'] = !empty($module['quiz_questions']);
            $out[] = $module;
            $previousDone = $module['is_unlocked'] && $module['is_done'];
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

    private static function answerIsCorrect(array $question, $answer): bool
    {
        $type = $question['type'] ?? 'single_choice';
        if ($type === 'multiple_choice') {
            $given = is_array($answer) ? array_map('intval', $answer) : [];
            $correct = is_array($question['correct'] ?? null) ? array_map('intval', $question['correct']) : [(int) ($question['correct'] ?? -1)];
            sort($given);
            sort($correct);
            return $given === $correct;
        }
        if ($type === 'text') {
            $given = self::normalizeTextAnswer((string) $answer);
            if ($given === '') {
                return false;
            }
            $accepted = $question['accepted_answers'] ?? [];
            if (!$accepted) {
                return true;
            }
            foreach ($accepted as $expected) {
                if ($given === self::normalizeTextAnswer((string) $expected)) {
                    return true;
                }
            }
            return false;
        }
        return (int) $answer === (int) ($question['correct'] ?? -2);
    }

    private static function normalizeTextAnswer(string $answer): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $answer) ?? ''));
    }

    private static function nextSort(int $courseId): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM course_modules WHERE course_id = ?');
        $stmt->execute([$courseId]);
        return (int) $stmt->fetchColumn();
    }
}
