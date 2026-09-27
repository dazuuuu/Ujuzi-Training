<?php

return [
    'up' => static function (PDO $pdo): void {
        // Attachment providers used to "join" organisations providing courses
        // like tutors do: a pending request the organisation had to approve.
        // They now choose where they appear themselves (attachment_provider_
        // audiences). Turn every such request or approval into audience rows,
        // then drop the memberships so no approval step is left.
        $rows = $pdo->query(
            "SELECT m.id, m.user_id, m.organisation_id, m.category_ids
             FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'attachment_trainer' AND m.status IN ('pending', 'approved')"
        )->fetchAll(PDO::FETCH_ASSOC);

        $categoriesOf = $pdo->prepare('SELECT id FROM organisation_categories WHERE organisation_id = ? AND is_active = 1');
        $categoryInOrg = $pdo->prepare('SELECT id FROM organisation_categories WHERE id = ? AND organisation_id = ?');
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO attachment_provider_audiences (provider_user_id, organisation_id, category_id) VALUES (?, ?, ?)'
        );
        foreach ($rows as $row) {
            $orgId = (int) $row['organisation_id'];
            $picked = json_decode((string) ($row['category_ids'] ?? ''), true);
            $picked = is_array($picked) ? array_filter(array_map('intval', $picked)) : [];
            $categoryIds = [];
            if ($picked) {
                foreach ($picked as $id) {
                    $categoryInOrg->execute([$id, $orgId]);
                    if ($categoryInOrg->fetchColumn()) {
                        $categoryIds[] = $id;
                    }
                }
            } else {
                // No categories named means the whole organisation.
                $categoriesOf->execute([$orgId]);
                $categoryIds = array_map('intval', $categoriesOf->fetchAll(PDO::FETCH_COLUMN));
            }
            foreach ($categoryIds as $categoryId) {
                $insert->execute([(int) $row['user_id'], $orgId, $categoryId]);
            }
        }

        $pdo->exec(
            "DELETE m FROM organisation_memberships m
             INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = 'attachment_trainer'"
        );

        // Approving such a request also made the course organisation the
        // provider's own organisation when they had none. Undo that: a
        // provider never belongs to an organisation providing courses.
        $pdo->exec(
            "UPDATE users u
             INNER JOIN roles r ON r.id = u.role_id
             SET u.organisation_id = NULL
             WHERE r.slug = 'attachment_trainer'
               AND u.organisation_id IN (
                   SELECT org_id FROM (
                       SELECT DISTINCT a.organisation_id AS org_id
                       FROM users a
                       INNER JOIN roles ar ON ar.id = a.role_id
                       WHERE ar.slug = 'organisation_admin' AND a.organisation_id IS NOT NULL
                   ) AS course_orgs
               )"
        );

        // The profile form's "Organisation" picker is what filed those
        // requests. Remove it from forms used only by attachment providers.
        $pdo->exec(
            "DELETE f FROM form_fields f
             WHERE f.field_type = 'organisation'
               AND f.form_id IN (
                   SELECT form_id FROM (
                       SELECT fr.form_id
                       FROM form_roles fr
                       INNER JOIN roles r ON r.id = fr.role_id
                       GROUP BY fr.form_id
                       HAVING SUM(r.slug = 'attachment_trainer') > 0 AND SUM(r.slug <> 'attachment_trainer') = 0
                   ) AS provider_forms
               )"
        );
    },
    'down' => static function (PDO $pdo): void {
        // One-way data cleanup: the old requests are not recreated.
    },
];
