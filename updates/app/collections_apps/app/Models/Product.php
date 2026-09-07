<?php

namespace App\Models;

use App\Core\Database;

class Product
{
    public const OCCASIONS = [
        'casual' => 'Casual Wear',
        'work' => 'Work & Business',
        'evening' => 'Evening Events',
        'travel' => 'Travel',
        'gifting' => 'Gifting',
        'ceremony' => 'Ceremonies',
    ];

    public static function all(): array
    {
        self::expireMaturedOffers();
        $rows = Database::connection()->query('SELECT * FROM products ORDER BY id ASC')->fetchAll();
        return array_map([self::class, 'toArray'], $rows);
    }

    public static function findByCode(string $code): ?array
    {
        self::expireMaturedOffers();
        $stmt = Database::connection()->prepare('SELECT * FROM products WHERE product_code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ? self::toArray($row) : null;
    }

    public static function findById(int $id): ?array
    {
        self::expireMaturedOffers();
        $stmt = Database::connection()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::toArray($row) : null;
    }

    /** Raw DB row (not shaped for the storefront) — used by admin forms/listings. */
    public static function findRawById(int $id): ?array
    {
        self::expireMaturedOffers();
        $stmt = Database::connection()->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function search(string $term = ''): array
    {
        self::expireMaturedOffers();
        $pdo = Database::connection();
        if ($term === '') {
            return $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
        }
        $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? OR product_code LIKE ? ORDER BY id DESC');
        $stmt->execute(["%$term%", "%$term%"]);
        return $stmt->fetchAll();
    }

    public static function onOffer(): array
    {
        self::expireMaturedOffers();
        return Database::connection()->query('SELECT * FROM products WHERE is_sale = 1 ORDER BY updated_at DESC')->fetchAll();
    }

    public static function notOnOffer(): array
    {
        self::expireMaturedOffers();
        return Database::connection()->query('SELECT id, name, product_code, price FROM products WHERE is_sale = 0 ORDER BY name ASC')->fetchAll();
    }

    public static function activeOccasions(): array
    {
        self::expireMaturedOffers();
        $rows = Database::connection()
            ->query("SELECT occasion, COUNT(*) AS product_count FROM products WHERE occasion IS NOT NULL AND occasion <> '' GROUP BY occasion ORDER BY product_count DESC, occasion ASC")
            ->fetchAll();

        $occasions = [];
        foreach ($rows as $row) {
            $key = (string) $row['occasion'];
            $occasions[] = [
                'id' => 'occasion:' . $key,
                'key' => $key,
                'label' => self::OCCASIONS[$key] ?? ucwords(str_replace(['-', '_'], ' ', $key)),
                'productCount' => (int) $row['product_count'],
            ];
        }
        return $occasions;
    }

    public static function nextProductCode(): string
    {
        $max = (int) Database::connection()
            ->query("SELECT MAX(CAST(SUBSTRING_INDEX(product_code, '-', -1) AS UNSIGNED)) FROM products WHERE product_code LIKE 'pentagon-%'")
            ->fetchColumn();
        return 'pentagon-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        $data['product_code'] = self::nextProductCode();
        $cols = implode(', ', array_keys($data));
        $params = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $pdo = Database::connection();
        $pdo->prepare("INSERT INTO products ($cols) VALUES ($params)")->execute($data);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE products SET ' . implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data))) . ' WHERE id = :id';
        $data['id'] = $id;
        Database::connection()->prepare($sql)->execute($data);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    }

    public static function updateOfferPricing(int $id, float $price, ?float $originalPrice, ?string $offerImage, ?string $offerEndsAt): void
    {
        Database::connection()
            ->prepare('UPDATE products SET price = ?, original_price = ?, offer_image = ?, offer_ends_at = ?, is_sale = 1 WHERE id = ?')
            ->execute([$price, $originalPrice, $offerImage, $offerEndsAt, $id]);
    }

    public static function removeFromOffers(int $id): void
    {
        Database::connection()->prepare('UPDATE products SET is_sale = 0, original_price = NULL, offer_image = NULL, offer_ends_at = NULL WHERE id = ?')->execute([$id]);
    }

    public static function addToOffers(int $id): void
    {
        Database::connection()->prepare('UPDATE products SET is_sale = 1, offer_ends_at = COALESCE(offer_ends_at, DATE_ADD(NOW(), INTERVAL 7 DAY)) WHERE id = ?')->execute([$id]);
    }

    public static function expireMaturedOffers(): void
    {
        Database::connection()
            ->exec('UPDATE products SET is_sale = 0, original_price = NULL, offer_image = NULL, offer_ends_at = NULL WHERE is_sale = 1 AND offer_ends_at IS NOT NULL AND offer_ends_at <= NOW()');
    }

    /** Converts a DB row into the shape storefront templates/JS expect. */
    public static function toArray(array $row): array
    {
        return [
            'id' => $row['product_code'],
            'dbId' => (int) $row['id'],
            'name' => $row['name'],
            'subtitle' => $row['subtitle'],
            'price' => (float) $row['price'],
            'originalPrice' => $row['original_price'] !== null ? (float) $row['original_price'] : null,
            'category' => $row['category_key'],
            'categories' => json_decode($row['category_keys'] ?? '[]', true) ?: [$row['category_key']],
            'subCategory' => $row['sub_category'],
            'occasion' => $row['occasion'] ?? null,
            'occasionLabel' => !empty($row['occasion']) ? (self::OCCASIONS[$row['occasion']] ?? ucwords(str_replace(['-', '_'], ' ', $row['occasion']))) : null,
            'colors' => json_decode($row['colors'] ?? '[]', true) ?: [],
            'sizes' => json_decode($row['sizes'] ?? '[]', true) ?: [],
            'images' => json_decode($row['images'] ?? '[]', true) ?: [],
            'offerImage' => $row['offer_image'] ?? null,
            'offerEndsAt' => $row['offer_ends_at'] ?? null,
            'description' => $row['description'],
            'details' => json_decode($row['details'] ?? '[]', true) ?: [],
            'fabric' => $row['fabric'],
            'fit' => $row['fit'],
            'isNew' => (bool) $row['is_new'],
            'isBestSeller' => (bool) $row['is_best_seller'],
            'isSale' => (bool) $row['is_sale'],
            'rating' => (float) $row['rating'],
            'reviewCount' => (int) $row['review_count'],
            'inStock' => (bool) $row['in_stock'],
            'featuredInLookbook' => (bool) $row['featured_in_lookbook'],
        ];
    }
}
