<?php

return [
    'up' => static function (PDO $pdo): void {
        // Repairs accounts caught by a bug: an email already used as one kind
        // of branch admin (or a student) kept its old role even when assigned
        // to the OTHER kind of branch, sending them to the wrong portal.
        $roleId = static function (PDO $pdo, string $slug): ?int {
            $id = $pdo->prepare('SELECT id FROM roles WHERE slug = ?');
            $id->execute([$slug]);
            $value = $id->fetchColumn();
            return $value ? (int) $value : null;
        };

        $courseBranchAdminRoleId = $roleId($pdo, 'course_branch_admin');
        $attachmentBranchAdminRoleId = $roleId($pdo, 'branch_admin');
        $promotableRoleIds = array_filter([
            $roleId($pdo, 'student'),
            $courseBranchAdminRoleId,
            $attachmentBranchAdminRoleId,
        ]);
        if (!$promotableRoleIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($promotableRoleIds), '?'));

        if ($courseBranchAdminRoleId) {
            $stmt = $pdo->prepare(
                "UPDATE users u
                 INNER JOIN organisation_branches b ON b.branch_admin_user_id = u.id
                 SET u.role_id = ?
                 WHERE b.organisation_id IS NOT NULL AND u.role_id IN ($placeholders)"
            );
            $stmt->execute(array_merge([$courseBranchAdminRoleId], $promotableRoleIds));
        }

        if ($attachmentBranchAdminRoleId) {
            $stmt = $pdo->prepare(
                "UPDATE users u
                 INNER JOIN organisation_branches b ON b.branch_admin_user_id = u.id
                 SET u.role_id = ?
                 WHERE b.owner_type = 'attachment_provider' AND u.role_id IN ($placeholders)"
            );
            $stmt->execute(array_merge([$attachmentBranchAdminRoleId], $promotableRoleIds));
        }
    },
    'down' => static function (PDO $pdo): void {
        // Not reversible — we don't know each account's prior role.
    },
];
