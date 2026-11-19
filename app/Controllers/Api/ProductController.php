<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\CatalogController;
use App\Core\{HttpException, Request, Response, View};
use App\Repositories\ProductRepository;
use App\Support\{ProductFilters, ProductQuote, Tailoring};

final class ProductController
{
    /**
     * GET /api/products?category=&dynasty[]=&gender=&q=&price=&min_price=&max_price=&featured=1&sort=&page=&per_page=
     *   format=html    → adds `html`: product cards only (home page tabs)
     *   format=catalog → adds the full catalog results block + heading/count (catalog page)
     *   otherwise      → JSON items
     */
    public function index(Request $req): void
    {
        $format = $req->query('format');
        $filters = ProductFilters::fromQuery($req->query,
            $format === 'catalog' ? CatalogController::PER_PAGE : 12);

        if ($format === 'catalog') {
            Response::json(CatalogController::results($filters));
        }

        $result = ProductRepository::search($filters, lang());
        if ($format === 'html') {
            $result['html'] = View::partial('partials/product-grid', ['products' => $result['items']]);
            unset($result['items']);
        }
        Response::json($result);
    }

    /**
     * POST /api/products/{slug}/quote   body: {fabric, color, size, qty, measurements?, check?}
     * Returns price/stock for the selection. With check=true it also validates the order
     * (stock, custom measurements) and answers 422 with field errors when something is wrong.
     */
    public function quote(Request $req): void
    {
        $product = ProductRepository::findBySlug($req->params['slug'], lang());
        if (!$product) throw HttpException::notFound(t('errors.productNotFound'));

        $quote = ProductQuote::quote($product, [
            'fabric' => $req->input('fabric'),
            'color'  => $req->input('color'),
            'size'   => $req->input('size'),
            'qty'    => $req->input('qty', 1),
        ]);

        $measurements = (array) $req->input('measurements', []);
        if ($quote['selection']['size'] === Tailoring::CUSTOM) {
            $quote['suggested_size'] = Tailoring::suggestSize($measurements);
        }

        if ($req->input('check')) {
            $errors = [];
            if ($quote['selection']['size'] === Tailoring::CUSTOM) {
                [$clean, $errors] = Tailoring::validate($measurements);
                $quote['measurements'] = $clean;
            }
            if (!$quote['available']) $errors['_stock'] = $quote['status_text'];
            if ($errors) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
            $quote['ok'] = true;
            $quote['message'] = t('pdp.added');
        }
        $quote['breakdown_html'] = View::partial('partials/price-breakdown', ['quote' => $quote]);
        Response::json($quote);
    }

    /** GET /api/products/{slug} */
    public function show(Request $req): void
    {
        $product = ProductRepository::findBySlug($req->params['slug'], lang());
        if (!$product) throw HttpException::notFound(t('errors.productNotFound'));
        Response::json($product);
    }
}
