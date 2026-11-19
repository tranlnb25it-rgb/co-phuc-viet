<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};
use App\Repositories\{MetaRepository, ProductRepository};
use App\Support\ProductFilters;

final class CatalogController
{
    public const PER_PAGE = 9;

    /** GET /bo-suu-tap — full page, rendered from the query string (works without JavaScript). */
    public function index(Request $req): void
    {
        $filters = ProductFilters::fromQuery($req->query, self::PER_PAGE);
        $results = self::results($filters);

        View::page('pages/catalog', [
            'title'      => $results['title'],
            'filters'    => $filters,
            'categories' => MetaRepository::categoryTree(lang()),
            'dynasties'  => MetaRepository::dynasties(lang()),
            'results'    => $results,
        ]);
    }

    /**
     * Search + render the results block. Used by the page above and by the Ajax endpoint
     * /api/products?format=catalog, so both always produce identical markup.
     */
    public static function results(array $filters): array
    {
        $l = lang();
        $search = ProductRepository::search($filters, $l);
        $filters['page'] = $search['page'];   // clamped to the last page

        $categories = MetaRepository::categoriesFlat($l);
        $chips = ProductFilters::chips($filters, $categories, MetaRepository::dynasties($l));

        $category = $filters['category'] ? (array_column($categories, null, 'slug')[$filters['category']] ?? null) : null;
        $title = $category['name'] ?? t('catalog.title');
        $description = $category['description'] ?? t('catalog.intro');

        $from = $search['total'] ? ($search['page'] - 1) * $search['per_page'] + 1 : 0;
        $to   = min($search['total'], $search['page'] * $search['per_page']);

        return [
            'html' => View::partial('partials/catalog-results', [
                'products' => $search['items'],
                'search'   => $search,
                'filters'  => $filters,
                'chips'    => $chips,
            ]),
            'total'        => $search['total'],
            'page'         => $search['page'],
            'pages'        => $search['pages'],
            'count_text'   => $search['total']
                ? t('catalog.count', ['from' => $from, 'to' => $to, 'total' => $search['total']])
                : t('catalog.countNone'),
            'title'        => $title,
            'description'  => $description,
            // number of panel filters applied (search box excluded) — badge on the mobile "Filters" button
            'active_count' => count($chips) - ($filters['q'] !== '' ? 1 : 0),
            'url'          => ProductFilters::url($filters),
        ];
    }
}
