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
            'INSERT INTO course_modules (course_id, title, description, summary, notes, duration_minutes, video_source, video_path, video_url, materials, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $sort,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        Database::connection()->prepare(
            'UPDATE course_modules
             SET title = ?, description = ?, summary = ?, notes = ?, duration_minutes = ?, video_source = ?, video_path = ?, video_url = ?, materials = ?
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

    public static function hydrate(array $row): array
    {
        $materials = $row['materials'] ?? null;
        if (is_string($materials) && $materials !== '') {
            $row['materials'] = json_decode($materials, true) ?: [];
        } elseif (!is_array($materials)) {
            $row['materials'] = [];
        }
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
