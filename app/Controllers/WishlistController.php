<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, View};
use App\Repositories\ProductRepository;
use App\Support\{Customer, Wishlist};

final class WishlistController
{
    /** GET /yeu-thich — works for guests (session list) and customers (saved in the account). */
    public function index(Request $req): void
    {
        $data = ['title' => t('wishlist.title'), 'products' => $this->products()];
        if (Customer::user()) {
            View::page('account/shell', ['title' => t('wishlist.title'), 'section' => 'wishlist',
                                         'body' => View::partial('pages/wishlist', $data + ['inAccount' => true])]);
        }
        View::page('pages/wishlist', $data + ['inAccount' => false]);
    }

    /** POST /api/wishlist/{id} — toggle; returns the new state and the header count. */
    public function toggle(Request $req): void
    {
        $saved = Wishlist::toggle((int) $req->params['id']);
        $out = ['ok' => true, 'saved' => $saved, 'count' => Wishlist::count(),
                'message' => t($saved ? 'wishlist.added' : 'wishlist.removed')];
        // on the wishlist page itself, send back the re-rendered grid
        if ($req->input('view') === 'page') {
            $out['html'] = View::partial('partials/wishlist-grid', ['products' => $this->products()]);
        }
        Response::json($out);
    }

    private function products(): array
    {
        $ids = Wishlist::ids();
        return $ids ? ProductRepository::search(['ids' => $ids, 'per_page' => 48], lang())['items'] : [];
    }
}
