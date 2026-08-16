<?php
return [
    'up' => "INSERT INTO roles (slug, name, description, has_admin_features, is_under_organisation, can_manage_users, managed_role_slugs, sort_order)
        VALUES
        ('organisation_admin', 'Organisation Admin', 'Manages an organisation and the trainers, attachment trainers, and students under it. Has admin-like tools inside that organisation.', 1, 0, 1, '[\"trainer\",\"attachment_trainer\",\"student\"]', 10),
        ('attachment_trainer', 'Attachment Trainer', 'Has admin-like tools for students in their organisation. Works under the organisation admin.', 1, 1, 1, '[\"student\"]', 20),
        ('trainer', 'Trainer / Tutor / Teacher', 'Delivers training. Lives under organisation power and does not have admin tools.', 0, 1, 0, '[]', 30),
        ('student', 'Student', 'Learner account. Lives under organisation power and fills assigned profile forms.', 0, 1, 0, '[]', 40)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            has_admin_features = VALUES(has_admin_features),
            is_under_organisation = VALUES(is_under_organisation),
            can_manage_users = VALUES(can_manage_users),
            managed_role_slugs = VALUES(managed_role_slugs),
            sort_order = VALUES(sort_order)",
    'down' => "DELETE FROM roles WHERE slug IN ('organisation_admin','attachment_trainer','trainer','student')",
];
