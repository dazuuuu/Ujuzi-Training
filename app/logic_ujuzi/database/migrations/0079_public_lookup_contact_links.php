<?php

return [
    'up' => static function (PDO $pdo): void {
        // Sites that already saved their navbar: add the new Student lookup and
        // Contact pages to the navbar and footer links (Super Admin can remove them).
        $stmt = $pdo->prepare("SELECT setting_value FROM store_settings WHERE setting_key = 'page_site'");
        $stmt->execute();
        $site = json_decode((string) $stmt->fetchColumn(), true);
        if (!is_array($site)) {
            return; // never saved: the defaults already include them
        }
        foreach (['nav_links', 'footer_links'] as $key) {
            $links = is_array($site[$key] ?? null) ? $site[$key] : [];
            $urls = array_column($links, 'url');
            foreach ([['label' => 'Student lookup', 'url' => '/student-lookup'], ['label' => 'Contact', 'url' => '/contact']] as $link) {
                if (!in_array($link['url'], $urls, true)) {
                    $links[] = $link;
                }
            }
            $site[$key] = $links;
        }
        $pdo->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'page_site'")
            ->execute([json_encode($site, JSON_UNESCAPED_UNICODE)]);
    },
    'down' => static function (PDO $pdo): void {
    },
];
