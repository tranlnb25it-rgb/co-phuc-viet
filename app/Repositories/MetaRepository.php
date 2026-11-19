<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Categories, dynasties and fabrics. $l is always 'vi' or 'en' (from Lang::current()).
 * Results are memoised per request because header, footer and pages all use them.
 */
final class MetaRepository
{
    private static array $memo = [];

    private static function memo(string $key, callable $fn): array
    {
        return self::$memo[$key] ??= $fn();
    }

    /** Top-level categories with nested children and active product counts (children included). */
    public static function categoryTree(string $l): array
    {
        return self::memo("tree.$l", function () use ($l) {
            $rows = Database::all(
                "SELECT c.id, c.parent_id, c.slug, c.name_$l AS name, c.description_$l AS description, c.image_url,
                        (SELECT COUNT(*) FROM products p
                           JOIN categories pc ON pc.id = p.category_id
                          WHERE p.is_active = 1 AND (pc.id = c.id OR pc.parent_id = c.id)) AS product_count
                   FROM categories c
               ORDER BY c.parent_id IS NOT NULL, c.sort_order");
            $byId = [];
            foreach ($rows as $r) { $r['children'] = []; $byId[$r['id']] = $r; }
            foreach (array_reverse($byId, true) as $id => $r) {
                if ($r['parent_id'] && isset($byId[$r['parent_id']])) {
                    array_unshift($byId[$r['parent_id']]['children'], $byId[$id]);
                    unset($byId[$id]);
                }
            }
            return array_values($byId);
        });
    }

    /** Flat list of all categories (parents followed by their children). */
    public static function categoriesFlat(string $l): array
    {
        $flat = [];
        foreach (self::categoryTree($l) as $c) {
            $flat[] = $c;
            foreach ($c['children'] as $child) $flat[] = $child;
        }
        return $flat;
    }

    public static function dynasties(string $l): array
    {
        return self::memo("dyn.$l", fn() =>
            Database::all("SELECT id, slug, name_$l AS name, period FROM dynasties ORDER BY sort_order"));
    }

    public static function fabrics(string $l): array
    {
        return self::memo("fab.$l", fn() =>
            Database::all("SELECT id, slug, name_$l AS name, price_modifier FROM fabrics ORDER BY id"));
    }
}
