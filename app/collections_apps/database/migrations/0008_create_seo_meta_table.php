<?php
/**
 * page_key identifies what the SEO entry belongs to:
 *   'home'                    — the storefront landing page
 *   'category:{category_key}' — e.g. 'category:outerwear'
 *   'product:{product_code}'  — e.g. 'product:pentagon-001'
 */
return [
    'up' => "CREATE TABLE IF NOT EXISTS seo_meta (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        page_key VARCHAR(191) NOT NULL UNIQUE,
        meta_title VARCHAR(255) DEFAULT NULL,
        meta_description VARCHAR(500) DEFAULT NULL,
        meta_keywords VARCHAR(500) DEFAULT NULL,
        featured_image VARCHAR(500) DEFAULT NULL,
        tags VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    'down' => 'DROP TABLE IF EXISTS seo_meta',
];
