<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{AccountService, OrderService};
use App\Support\{Cart, Customer};

final class CheckoutController
{
    /** GET /thanh-toan */
    public function show(Request $req): void
    {
        $this->render();
    }

    /**
     * POST /thanh-toan (classic form, works without JS) and POST /api/orders (Ajax).
     * Ajax gets JSON {redirect} or 422 {details}; the classic form gets a redirect or the page with errors.
     */
    public function place(Request $req): void
    {
        [$customer, $errors] = OrderService::validateCustomer($req->body());

        if (!$errors && !Cart::summary(lang())['all_available']) {
            $errors['_cart'] = t('checkout.errors.unavailable');
        }
        if ($errors) {
            if ($req->isAjax()) throw HttpException::badRequest(t('pdp.errors.fix'), $errors);
            $this->render($customer, $errors, 422);
        }

        try {
            $code = OrderService::place($customer);
            if (($uid = Customer::id()) && $req->input('save_address')) AccountService::rememberAddress($uid, $customer);
        } catch (HttpException $e) {   // e.g. someone bought the last item a moment ago
            if ($req->isAjax()) throw HttpException::badRequest($e->getMessage(), ['_cart' => $e->getMessage()]);
            $this->render($customer, ['_cart' => $e->getMessage()], 422);
        }

        $redirect = url('/dat-hang-thanh-cong/' . $code);
        if ($req->isAjax()) Response::json(['ok' => true, 'code' => $code, 'redirect' => $redirect]);
        Response::redirect($redirect, 303);
    }

    /** GET /dat-hang-thanh-cong/{code} — only for orders placed from this browser session. */
    public function success(Request $req): void
    {
        $code = $req->params['code'];
        if (!in_array($code, $_SESSION['orders'] ?? [], true)) throw HttpException::notFound();
        $order = OrderService::find($code);
        if (!$order) throw HttpException::notFound();

        View::page('pages/order-success', ['title' => t('order.title'), 'order' => $order]);
    }

    private function render(array $old = [], array $errors = [], int $status = 200): never
    {
        $member = Customer::user();
        $addresses = $member ? AccountService::addresses($member['id']) : [];
        if ($member && !$old) {   // first visit: pre-fill from the default address, else from the profile
            $d = $addresses[0] ?? null;
            $old = [
                'customer_name' => $d['recipient_name'] ?? $member['name'],
                'phone'         => $d['phone'] ?? ($member['phone'] ?? ''),
                'email'         => $member['email'],
                'address'       => $d['address'] ?? '',
                'city'          => $d['city'] ?? '',
                'address_id'    => $d['id'] ?? null,
            ];
        }
        View::page('pages/checkout', [
            'title'     => t('checkout.title'),
            'cart'      => Cart::summary(lang()),
            'old'       => $old + ['payment_method' => 'COD'],
            'errors'    => $errors,
            'member'    => $member,
            'addresses' => $addresses,
        ], $status);
    }
}
