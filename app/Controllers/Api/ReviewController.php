<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\{HttpException, Request, Response, View};
use App\Repositories\ProductRepository;
use App\Services\Reviews;

/** Storefront reviews: list (filters / sort / "show more"), submit with photos, "helpful" votes. */
final class ReviewController
{
    /** GET /api/products/{slug}/reviews?rating=&photos=&sort=&page= → {html, total, page, pages} */
    public function index(Request $req): void
    {
        $product = $this->product($req);
        $list = Reviews::forProduct((int) $product['id'], [
            'rating' => $req->query('rating'), 'photos' => $req->query('photos'),
            'sort' => $req->query('sort'), 'page' => $req->query('page', 1),
        ]);
        Response::json([
            'html'  => View::partial('partials/reviews/items', ['list' => $list]),
            'total' => $list['total'], 'page' => $list['page'], 'pages' => $list['pages'],
        ]);
    }

    /** POST /api/products/{slug}/reviews (multipart: rating, title, body, fit, images[]) */
    public function store(Request $req): void
    {
        $product = $this->product($req);
        $status = Reviews::submit((int) $product['id'], $req->body(), $_FILES);
        Response::json(['ok' => true, 'status' => $status,
                        'message' => t($status === 'approved' ? 'reviews.thanksApproved' : 'reviews.thanksPending')]);
    }

    /** POST /api/reviews/{id}/helpful → {count} */
    public function helpful(Request $req): void
    {
        $count = Reviews::vote((int) $req->params['id']);
        Response::json(['ok' => true, 'count' => $count, 'label' => t('reviews.helpfulCount', ['count' => $count]),
                        'message' => t('reviews.voted')]);
    }

    private function product(Request $req): array
    {
        $p = ProductRepository::findBySlug($req->params['slug'], lang());
        if (!$p) throw HttpException::notFound(t('errors.productNotFound'));
        return $p;
    }
}
