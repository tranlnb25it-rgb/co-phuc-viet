<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Pricing;

final class ProductRepository
{
    public const SORTS = [
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'price ASC',      // effective price (sale-aware), see Pricing::SQL_PRICE
        'price_desc' => 'price DESC',
        'discount'   => 'on_sale DESC, (p.base_price - price) / p.base_price DESC',
        'name'       => 'name ASC',
        'rating'     => 'p.rating_count > 0 DESC, p.rating_avg DESC, p.rating_count DESC',
    ];

    /**
     * Filtered, paginated product list for cards.
     *
     * @param array{category?:string, dynasty?:string|string[], gender?:string, q?:string, min_price?:int,
     *              max_price?:int, featured?:bool, sort?:string, page?:int, per_page?:int} $f
     * @return array{items: array, total: int, page: int, per_page: int, pages: int}
     */
    public static function search(array $f, string $l): array
    {
        $where  = ['p.is_active = 1'];
        $params = [];

        if (!empty($f['category'])) {
            $where[] = '(c.slug = :cat OR pc.slug = :cat2)';      // parent slug includes its children
            $params['cat'] = $params['cat2'] = $f['category'];
        }
        if (!empty($f['dynasty'])) {                               // one slug or a list (OR)
            $slugs = array_values((array) $f['dynasty']);
            $keys = [];
            foreach ($slugs as $i => $s) { $keys[] = ":dyn$i"; $params["dyn$i"] = $s; }
            $where[] = 'd.slug IN (' . implode(', ', $keys) . ')';
        }
        if (!empty($f['gender']) && in_array($f['gender'], ['nam', 'nu'], true)) {
            $where[] = "(p.gender = :gender OR p.gender = 'unisex')";
            $params['gender'] = $f['gender'];
        }
        if (!empty($f['q'])) {
            $where[] = '(p.name_vi LIKE :q1 OR p.name_en LIKE :q2 OR c.name_vi LIKE :q3 OR c.name_en LIKE :q4)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        if (isset($f['min_price'])) { $where[] = Pricing::SQL_PRICE . ' >= :minp'; $params['minp'] = (int) $f['min_price']; }
        if (isset($f['max_price'])) { $where[] = Pricing::SQL_PRICE . ' <= :maxp'; $params['maxp'] = (int) $f['max_price']; }
        if (!empty($f['featured']))  { $where[] = 'p.is_featured = 1'; }
        if (!empty($f['on_sale']))   { $where[] = Pricing::SQL_ON_SALE; }
        if (!empty($f['ids'])) {                                   // explicit list, keeps the given order
            $ids = array_map('intval', (array) $f['ids']);
            $where[] = 'p.id IN (' . implode(',', $ids) . ')';
            $orderByIds = 'FIELD(p.id, ' . implode(',', $ids) . ')';
        }

        $from = "FROM products p
                 JOIN categories c       ON c.id = p.category_id
                 LEFT JOIN categories pc ON pc.id = c.parent_id
                 LEFT JOIN dynasties d   ON d.id = p.dynasty_id
                 WHERE " . implode(' AND ', $where);

        $total   = (int) Database::one("SELECT COUNT(*) AS n $from", $params)['n'];
        $perPage = max(1, min(48, (int) ($f['per_page'] ?? 12)));
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $order   = $orderByIds ?? self::SORTS[$f['sort'] ?? 'newest'] ?? self::SORTS['newest'];
        $offset  = ($page - 1) * $perPage;

        $items = Database::all(
            "SELECT p.id, p.slug, p.name_$l AS name, p.short_description_$l AS short_description,
                    p.base_price, p.sale_price, p.sale_starts_at, p.sale_ends_at,
                    " . Pricing::SQL_PRICE . " AS price, " . Pricing::SQL_ON_SALE . " AS on_sale,
                    p.gender, p.allow_custom_tailoring, p.is_featured, p.rating_avg, p.rating_count,
                    c.slug AS category_slug, c.name_$l AS category_name,
                    d.slug AS dynasty_slug, d.name_$l AS dynasty_name,
                    (SELECT url FROM product_images i WHERE i.product_id = p.id AND i.view_type = 'front'
                      ORDER BY i.sort_order LIMIT 1) AS image_front,
                    (SELECT url FROM product_images i WHERE i.product_id = p.id AND i.view_type = 'back'
                      ORDER BY i.sort_order LIMIT 1) AS image_back,
                    (SELECT color_hex FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS tint,
                    (SELECT COALESCE(SUM(stock), 0) FROM product_variants v WHERE v.product_id = p.id) AS stock
             $from
             ORDER BY $order
             LIMIT $perPage OFFSET $offset",
            $params);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => $pages];
    }

    /** Up to $limit other products: same category first, then same era, then featured. */
    public static function related(array $product, string $l, int $limit = 4): array
    {
        $ids = array_column(Database::all(
            "SELECT p.id FROM products p
              WHERE p.is_active = 1 AND p.id <> :id
              ORDER BY (p.category_id = (SELECT category_id FROM products WHERE id = :id2)) DESC,
                       (p.dynasty_id <=> (SELECT dynasty_id FROM products WHERE id = :id3)) DESC,
                       p.is_featured DESC, p.id
              LIMIT $limit",
            ['id' => $product['id'], 'id2' => $product['id'], 'id3' => $product['id']]), 'id');
        return $ids ? self::search(['ids' => $ids, 'per_page' => $limit], $l)['items'] : [];
    }

    /** Full product with images, variants, available fabrics/colours/sizes. Null if not found. */
    public static function findBySlug(string $slug, string $l): ?array
    {
        static $memo = [];   // the cart looks up the same products several times per request
        $key = "$l|$slug";
        if (!array_key_exists($key, $memo)) $memo[$key] = self::loadBySlug($slug, $l);
        return $memo[$key];
    }

    private static function loadBySlug(string $slug, string $l): ?array
    {
        $p = Database::one(
            "SELECT p.id, p.slug, p.name_$l AS name, p.short_description_$l AS short_description,
                    p.historical_context_$l AS historical_context, p.base_price, p.sale_price, p.sale_starts_at, p.sale_ends_at, p.gender,
                    p.allow_custom_tailoring, p.custom_tailoring_fee, p.is_featured, p.rating_avg, p.rating_count,
                    c.slug AS category_slug, c.name_$l AS category_name,
                    d.slug AS dynasty_slug, d.name_$l AS dynasty_name, d.period AS dynasty_period
               FROM products p
               JOIN categories c     ON c.id = p.category_id
               LEFT JOIN dynasties d ON d.id = p.dynasty_id
              WHERE p.slug = :slug AND p.is_active = 1",
            ['slug' => $slug]);
        if (!$p) return null;

        $p['images'] = Database::all(
            "SELECT id, url, view_type FROM product_images WHERE product_id = :id
             ORDER BY FIELD(view_type, 'front', 'back', 'detail'), sort_order", ['id' => $p['id']]);

        $p['variants'] = Database::all(
            "SELECT v.id, v.size, v.fabric_id, f.slug AS fabric_slug, f.name_$l AS fabric_name,
                    COALESCE(f.price_modifier, 0) AS price_modifier,
                    v.color_$l AS color, v.color_hex, v.stock, v.sku
               FROM product_variants v
               LEFT JOIN fabrics f ON f.id = v.fabric_id
              WHERE v.product_id = :id
              ORDER BY FIELD(v.size, 'S', 'M', 'L', 'XL', 'FREE'), f.id, v.id", ['id' => $p['id']]);

        return $p;
    }
}
