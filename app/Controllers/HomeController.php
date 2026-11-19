<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, View};
use App\Repositories\{MetaRepository, ProductRepository};

final class HomeController
{
    /** Categories featured on the home page, in display order. */
    private const FEATURED_CATEGORIES = ['ao-nhat-binh', 'ao-tac', 'ao-giao-linh', 'phu-kien'];

    public function index(Request $req): void
    {
        $l = lang();
        $tree = MetaRepository::categoryTree($l);
        $bySlug = array_column($tree, null, 'slug');
        $featuredCategories = array_values(array_filter(
            array_map(fn($s) => $bySlug[$s] ?? null, self::FEATURED_CATEGORIES)));

        View::page('pages/home', [
            'title'              => t('home.metaTitle'),
            'featuredCategories' => $featuredCategories,
            'dynasties'          => MetaRepository::dynasties($l),
            // First tab is rendered server-side; other tabs load via Ajax (/api/products?format=html)
            'featuredProducts'   => ProductRepository::search(['featured' => true, 'per_page' => 8], $l)['items'],
        ]);
    }
}
