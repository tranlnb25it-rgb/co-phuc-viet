<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{HttpException, Request, View};
use App\Repositories\ProductRepository;
use App\Services\{AccountService, Reviews};
use App\Support\{Customer, ProductQuote, Tailoring};

final class ProductPageController
{
    /** GET /san-pham/{slug} */
    public function show(Request $req): void
    {
        $l = lang();
        $product = ProductRepository::findBySlug($req->params['slug'], $l);
        if (!$product) throw HttpException::notFound(t('errors.productNotFound'));

        // Selection may come from the query string (e.g. shared links); otherwise sensible defaults.
        $selection = array_merge(ProductQuote::defaults($product), array_filter([
            'fabric' => $req->query('fabric'),
            'color'  => $req->query('color'),
            'size'   => $req->query('size'),
        ]));

        View::page('pages/product', [
            'title'     => $product['name'],
            'product'   => $product,
            'options'   => ProductQuote::options($product),
            'quote'     => ProductQuote::quote($product, $selection),
            'sizeChart' => Tailoring::SIZE_CHART,
            'fields'    => Tailoring::FIELDS,
            'related'   => ProductRepository::related($product, $l),
            'reviewSummary' => Reviews::summary((int) $product['id']),
            'reviewList'    => Reviews::forProduct((int) $product['id'], []),
            'eligibility'   => Reviews::eligibility((int) $product['id']),
            // signed-in customers can fill the tailoring form from a saved measurement profile
            'savedMeasurements' => ($uid = Customer::id()) && $product['allow_custom_tailoring']
                ? array_map(fn($p) => ['id' => (int) $p['id'], 'name' => $p['name'], 'values' => AccountService::measurementValues($p)], AccountService::measurements($uid))
                : [],
        ]);
    }
}
