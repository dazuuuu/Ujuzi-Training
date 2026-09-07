<?php

namespace App\Models;

use App\Core\Database;

/**
 * page_key identifies what the entry belongs to:
 *   'home'                    — the storefront landing page
 *   'category:{category_key}' — e.g. 'category:outerwear'
 *   'product:{product_code}'  — e.g. 'product:pentagon-001'
 */
class SeoMeta
{
    public static function find(string $pageKey): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM seo_meta WHERE page_key = ?');
        $stmt->execute([$pageKey]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT * FROM seo_meta')->fetchAll();
        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row['page_key']] = $row;
        }
        return $byKey;
    }

    public static function upsert(string $pageKey, array $fields): void
    {
        Database::connection()->prepare(
            'INSERT INTO seo_meta (page_key, meta_title, meta_description, meta_keywords, featured_image, tags)
             VALUES (:page_key, :meta_title, :meta_description, :meta_keywords, :featured_image, :tags)
             ON DUPLICATE KEY UPDATE
                meta_title = VALUES(meta_title), meta_description = VALUES(meta_description),
                meta_keywords = VALUES(meta_keywords), featured_image = VALUES(featured_image), tags = VALUES(tags)'
        )->execute([
            'page_key' => $pageKey,
            'meta_title' => $fields['meta_title'] ?: null,
            'meta_description' => $fields['meta_description'] ?: null,
            'meta_keywords' => $fields['meta_keywords'] ?: null,
            'featured_image' => $fields['featured_image'] ?: null,
            'tags' => $fields['tags'] ?: null,
        ]);
    }

    /**
     * Resolves the tags actually used to render <title>/<meta> for a page,
     * falling back to sensible auto-generated defaults when no custom SEO
     * entry exists yet for that page_key.
     */
    public static function resolve(string $pageKey, string $fallbackTitle, string $fallbackDescription, ?string $fallbackImage = null): array
    {
        $entry = self::find($pageKey);
        return [
            'title' => $entry['meta_title'] ?? $fallbackTitle,
            'description' => $entry['meta_description'] ?? $fallbackDescription,
            'keywords' => $entry['meta_keywords'] ?? '',
            'image' => $entry['featured_image'] ?? $fallbackImage,
            'tags' => $entry['tags'] ?? '',
        ];
    }
}
