<?php
declare(strict_types=1);

namespace App\Support;

use App\Repositories\ProductRepository;

/**
 * Normalises catalog filters from a query string, and builds URLs / "active filter" chips from them.
 * Shared by the catalog page (server render) and /api/products (Ajax).
 */
final class ProductFilters
{
    /** Price presets shown in the filter panel: slug => [min, max] (VND, inclusive). */
    public const PRICE_RANGES = [
        'duoi-1tr' => [null, 999_999],
        '1-3tr'    => [1_000_000, 2_999_999],
        '3-4tr'    => [3_000_000, 3_999_999],
        'tren-4tr' => [4_000_000, null],
    ];
    public const GENDERS = ['nam', 'nu'];

    /** @return array{category:?string, dynasty:string[], gender:?string, q:string, price:?string,
     *                min_price:?int, max_price:?int, featured:bool, sort:string, page:int, per_page:int} */
    public static function fromQuery(array $q, int $defaultPerPage = 12): array
    {
        $slug = fn($v) => is_string($v) && preg_match('/^[a-z0-9-]{1,120}$/', $v) ? $v : null;

        $dyn = $q['dynasty'] ?? [];
        $dyn = is_array($dyn) ? $dyn : explode(',', (string) $dyn);
        $dyn = array_values(array_unique(array_filter(array_map($slug, $dyn))));

        $price = isset($q['price']) && isset(self::PRICE_RANGES[$q['price']]) ? $q['price'] : null;
        [$min, $max] = $price ? self::PRICE_RANGES[$price] : [null, null];
        if (isset($q['min_price']) && is_numeric($q['min_price'])) $min = max(0, (int) $q['min_price']);
        if (isset($q['max_price']) && is_numeric($q['max_price'])) $max = max(0, (int) $q['max_price']);

        $sort = (string) ($q['sort'] ?? 'newest');

        return [
            'category'  => $slug($q['category'] ?? null),
            'dynasty'   => $dyn,
            'gender'    => in_array($q['gender'] ?? null, self::GENDERS, true) ? $q['gender'] : null,
            'q'         => mb_substr(trim((string) ($q['q'] ?? '')), 0, 100),
            'price'     => $price,
            'min_price' => $min,
            'max_price' => $max,
            'featured'  => !empty($q['featured']),
            'on_sale'   => !empty($q['sale']),
            'sort'      => isset(ProductRepository::SORTS[$sort]) ? $sort : 'newest',
            'page'      => max(1, (int) ($q['page'] ?? 1)),
            'per_page'  => max(1, min(48, (int) ($q['per_page'] ?? $defaultPerPage))),
        ];
    }

    /** Query params for a catalog URL (defaults omitted). $override lets callers change/remove keys. */
    public static function toQuery(array $f, array $override = []): array
    {
        $f = array_merge($f, $override);
        $out = [];
        if ($f['q'] !== '')        $out['q'] = $f['q'];
        if ($f['category'])        $out['category'] = $f['category'];
        if ($f['dynasty'])         $out['dynasty'] = array_values($f['dynasty']);
        if ($f['gender'])          $out['gender'] = $f['gender'];
        if ($f['price'])           $out['price'] = $f['price'];
        if (!empty($f['on_sale'])) $out['sale'] = 1;
        if ($f['sort'] !== 'newest') $out['sort'] = $f['sort'];
        if ($f['page'] > 1)        $out['page'] = $f['page'];
        return $out;
    }

    public static function url(array $f, array $override = []): string
    {
        // dynasty[]=le&dynasty[]=nguyen (no indices) so it matches the form's checkbox name "dynasty[]"
        $qs = preg_replace('/%5B\d+%5D=/', '%5B%5D=', http_build_query(self::toQuery($f, $override)));
        return url('/bo-suu-tap') . ($qs !== '' ? '?' . $qs : '');
    }

    /**
     * Chips for the filters currently applied, each with a URL that removes it.
     * @param array $categories flat list of ['slug' => …, 'name' => …]
     * @param array $dynasties  list of ['slug' => …, 'name' => …]
     * @return list<array{label:string, url:string}>
     */
    public static function chips(array $f, array $categories, array $dynasties): array
    {
        $catNames = array_column($categories, 'name', 'slug');
        $dynNames = array_column($dynasties, 'name', 'slug');
        $reset = ['page' => 1];
        $chips = [];

        if ($f['q'] !== '') {
            $chips[] = ['label' => '“' . $f['q'] . '”', 'url' => self::url($f, $reset + ['q' => ''])];
        }
        if ($f['category'] && isset($catNames[$f['category']])) {
            $chips[] = ['label' => $catNames[$f['category']], 'url' => self::url($f, $reset + ['category' => null])];
        }
        foreach ($f['dynasty'] as $d) {
            if (!isset($dynNames[$d])) continue;
            $chips[] = ['label' => $dynNames[$d],
                        'url' => self::url($f, $reset + ['dynasty' => array_values(array_diff($f['dynasty'], [$d]))])];
        }
        if ($f['gender']) {
            $chips[] = ['label' => t('product.gender.' . $f['gender']), 'url' => self::url($f, $reset + ['gender' => null])];
        }
        if ($f['price']) {
            $chips[] = ['label' => t('catalog.prices.' . $f['price']), 'url' => self::url($f, $reset + ['price' => null])];
        }
        if (!empty($f['on_sale'])) {
            $chips[] = ['label' => t('catalog.onSale'), 'url' => self::url($f, $reset + ['on_sale' => false])];
        }
        return $chips;
    }
}
