<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\{HttpException, Request, Response, View};
use App\Repositories\ProductRepository;
use App\Support\Cart;

/** Ajax cart. Every response carries the re-rendered drawer so the client just swaps HTML. */
final class CartController
{
    /** GET /api/cart  (?view=summary also returns the checkout sidebar) */
    public function show(Request $req): void
    {
        $this->respond($req->query('view') === 'summary');
    }

    /** POST /api/cart  {slug, fabric, color, size, qty, measurements} */
    public function add(Request $req): void
    {
        $slug = (string) $req->input('slug', '');
        $product = ProductRepository::findBySlug($slug, lang());
        if (!$product) throw HttpException::notFound(t('errors.productNotFound'));

        Cart::add($product, [
            'fabric'       => $req->input('fabric'),
            'color'        => $req->input('color'),
            'size'         => $req->input('size'),
            'qty'          => $req->input('qty', 1),
            'measurements' => $req->input('measurements', []),
        ]);
        $this->respond(false, t('cart.added', ['name' => $product['name']]));
    }

    /** PATCH /api/cart/{key}  {qty} */
    public function update(Request $req): void
    {
        Cart::update($req->params['key'], (int) $req->input('qty', 1));
        $this->respond((bool) $req->input('summary'));
    }

    /** DELETE /api/cart/{key} */
    public function remove(Request $req): void
    {
        Cart::remove($req->params['key']);
        $this->respond($req->query('view') === 'summary');
    }

    /** POST /api/cart/coupon  {code} */
    public function applyCoupon(Request $req): void
    {
        Cart::applyCoupon((string) $req->input('code', ''), lang());
        $this->respond(true, t('coupon.applied'));
    }

    /** DELETE /api/cart/coupon */
    public function removeCoupon(Request $req): void
    {
        Cart::removeCoupon();
        $this->respond(true, t('coupon.removed'));
    }

    private function respond(bool $withSummary, ?string $message = null): never
    {
        $cart = Cart::summary(lang());
        $payload = [
            'count'   => $cart['count'],
            'total'   => $cart['total'],
            'html'    => View::partial('partials/cart-panel', ['cart' => $cart]),
            'message' => $message,
        ];
        if ($withSummary) $payload['summary_html'] = View::partial('partials/order-summary', ['cart' => $cart]);
        Response::json($payload);
    }
}
