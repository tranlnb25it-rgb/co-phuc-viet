<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{HttpException, Request, Response, View};
use App\Services\AccountService;

/**
 * /tra-cuu-don-hang — guests check an order with its code + the phone used to order.
 * Failed lookups are throttled per session so codes can't be brute-forced.
 */
final class OrderLookupController
{
    private const MAX_FAILS = 8;
    private const LOCK_SECONDS = 600;

    /** GET /tra-cuu-don-hang */
    public function show(Request $req): void
    {
        View::page('pages/order-lookup', ['title' => t('lookup.title'), 'old' => ['code' => (string) $req->query('code', '')],
                                          'order' => null, 'error' => null]);
    }

    /** POST /tra-cuu-don-hang (classic form) and POST /api/orders/lookup (Ajax → {html}) */
    public function lookup(Request $req): void
    {
        $code = trim((string) $req->input('code', ''));
        $phone = trim((string) $req->input('phone', ''));
        $error = null;
        $order = null;

        $locked = max(0, (int) ($_SESSION['lookup_lock_until'] ?? 0) - time());
        if ($locked) {
            $error = t('lookup.locked', ['minutes' => (int) ceil($locked / 60)]);
        } elseif ($code === '' || $phone === '') {
            $error = t('lookup.required');
        } elseif (!($order = AccountService::lookup($code, $phone))) {
            $error = t('lookup.notFound');
            $_SESSION['lookup_fails'] = ($_SESSION['lookup_fails'] ?? 0) + 1;
            if ($_SESSION['lookup_fails'] >= self::MAX_FAILS) {
                $_SESSION['lookup_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['lookup_fails'] = 0;
            }
        } else {
            unset($_SESSION['lookup_fails']);
        }

        if ($req->isAjax()) {
            if ($error) throw HttpException::badRequest($error, ['_lookup' => $error]);
            Response::json(['ok' => true, 'html' => View::partial('partials/order-tracking', ['order' => $order, 'public' => true])]);
        }
        View::page('pages/order-lookup', ['title' => t('lookup.title'), 'old' => ['code' => $code, 'phone' => $phone],
                                          'order' => $order, 'error' => $error], $error ? 422 : 200);
    }
}
