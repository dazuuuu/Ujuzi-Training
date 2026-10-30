<?php

namespace App\Models;

use App\Core\Database;

/**
 * A trail of what admins (above all the website owner) do: each page they
 * open and each action they submit in the admin panel. Written from
 * BaseAdminController and read in the private developer console.
 */
class ActivityLog
{
    /** Records one admin request. Never throws — logging must not break a page. */
    public static function record(array $admin, string $method, string $path): void
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO admin_activity_log (admin_id, admin_email, admin_name, is_owner, method, path, action, ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) ($admin['id'] ?? 0) ?: null,
                mb_substr((string) ($admin['email'] ?? ''), 0, 255) ?: null,
                mb_substr((string) ($admin['name'] ?? ''), 0, 255) ?: null,
                !empty($admin['is_owner']) ? 1 : 0,
                strtoupper($method) === 'POST' ? 'POST' : 'GET',
                mb_substr($path, 0, 512),
                mb_substr(self::describe($method, $path), 0, 190),
                mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
            ]);
        } catch (\Throwable $e) {
            // Table not present yet (before this patch), or any write issue: ignore.
        }
    }

    /** Latest entries, newest first. */
    public static function recent(int $limit = 100, int $offset = 0): array
    {
        try {
            $limit = max(1, min(500, $limit));
            $offset = max(0, $offset);
            $stmt = Database::connection()->prepare(
                'SELECT * FROM admin_activity_log ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function total(): int
    {
        try {
            return (int) Database::connection()->query('SELECT COUNT(*) FROM admin_activity_log')->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** A short human label for a request, e.g. "Updated a user" or "Viewed Reports". */
    private static function describe(string $method, string $path): string
    {
        $isPost = strtoupper($method) === 'POST';
        $p = parse_url($path, PHP_URL_PATH) ?: $path;
        $p = preg_replace('#^/admin/?#', '', rtrim($p, '/'));
        if ($p === '') {
            return 'Opened the dashboard';
        }
        $parts = explode('/', $p);
        $section = ucwords(str_replace('-', ' ', $parts[0]));

        // Action verb from the trailing segment on POSTs.
        if ($isPost) {
            $tail = end($parts);
            $verbs = [
                'delete' => 'Deleted', 'destroy' => 'Deleted', 'remove' => 'Removed',
                'update' => 'Updated', 'status' => 'Changed status', 'unlock' => 'Unlocked',
                'approve' => 'Approved', 'reject' => 'Rejected', 'decide' => 'Decided on',
                'store' => 'Created', 'create' => 'Created', 'reset-password' => 'Reset a password',
                'run' => 'Ran', 'save' => 'Saved', 'import' => 'Imported', 'invite' => 'Invited',
            ];
            if (isset($verbs[$tail])) {
                return $verbs[$tail] . ' in ' . $section;
            }
            if (is_numeric($tail)) {
                return 'Saved a record in ' . $section;
            }
            return 'Submitted in ' . $section;
        }
        if (in_array(end($parts), ['create', 'edit'], true)) {
            return 'Opened a form in ' . $section;
        }
        return 'Viewed ' . $section;
    }
}
