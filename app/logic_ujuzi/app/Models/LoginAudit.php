<?php

namespace App\Models;

use App\Core\Database;

/** Who signed in, when, from where — and who tried and failed. Never throws. */
class LoginAudit
{
    public static function record(string $actorType, ?int $actorId, string $identifier, ?string $name, ?string $role, bool $success, ?string $detail = null): void
    {
        try {
            Database::connection()->prepare(
                'INSERT INTO login_audit (actor_type, actor_id, identifier, display_name, role_slug, success, detail, ip, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $actorType === 'admin' ? 'admin' : 'user', $actorId ?: null,
                mb_substr($identifier, 0, 255) ?: null, $name !== null ? mb_substr($name, 0, 255) : null, $role,
                $success ? 1 : 0, $detail !== null ? mb_substr($detail, 0, 255) : null,
                mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            // Table not installed yet: logging must never block a sign-in.
        }
    }

    /** @return array{rows: array, total: int} */
    public static function search(string $q, string $status, string $type, int $limit, int $offset): array
    {
        $where = [];
        $args = [];
        if ($q !== '') {
            $where[] = '(identifier LIKE ? OR display_name LIKE ? OR ip LIKE ?)';
            array_push($args, "%$q%", "%$q%", "%$q%");
        }
        if ($status === 'ok' || $status === 'failed') {
            $where[] = 'success = ?';
            $args[] = $status === 'ok' ? 1 : 0;
        }
        if ($type === 'user' || $type === 'admin') {
            $where[] = 'actor_type = ?';
            $args[] = $type;
        }
        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        try {
            $pdo = Database::connection();
            $count = $pdo->prepare('SELECT COUNT(*) FROM login_audit' . $sql);
            $count->execute($args);
            $stmt = $pdo->prepare('SELECT * FROM login_audit' . $sql . ' ORDER BY id DESC LIMIT ' . max(1, min(200, $limit)) . ' OFFSET ' . max(0, $offset));
            $stmt->execute($args);
            return ['rows' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn()];
        } catch (\Throwable $e) {
            return ['rows' => [], 'total' => 0];
        }
    }
}
