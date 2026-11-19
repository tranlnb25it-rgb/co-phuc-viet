<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{HttpException, Request, Response, View};
use App\Services\{AccountService, Reviews};
use App\Support\Customer;

/**
 * /tai-khoan/* — "My account". Each section is a normal PHP page; with ?fragment=1 over Ajax it returns
 * only the section HTML so the account menu can switch sections without a full reload.
 * The JSON endpoints under /api/account/* answer with the re-rendered section.
 */
final class AccountController
{
    /** section => [template, nav key, title key] */
    private const SECTIONS = [
        'overview'     => ['account/overview',     'account.nav.overview'],
        'orders'       => ['account/orders',       'account.orders.title'],
        'order'        => ['account/order',        'account.orders.title'],
        'addresses'    => ['account/addresses',    'account.addresses.title'],
        'measurements' => ['account/measurements', 'account.measurements.title'],
        'reviews'      => ['account/reviews',      'reviews.account.title'],
        'profile'      => ['account/profile',      'account.profile.title'],
    ];

    private function uid(): int
    {
        return (int) Customer::id();
    }

    // ---------------------------------------------------------------- pages

    public function overview(Request $req): void
    {
        $uid = $this->uid();
        $this->section($req, 'overview', [
            'stats'  => AccountService::stats($uid),
            'recent' => AccountService::orders($uid, 'all', 1, 3)['items'],
        ]);
    }

    public function orders(Request $req): void
    {
        $this->section($req, 'orders', ['list' => $this->orderList($req)]);
    }

    /** GET /api/account/orders — the list block only (tabs + pagination). */
    public function orderRows(Request $req): void
    {
        $list = $this->orderList($req);
        Response::json(['html' => View::partial('account/order-list', ['list' => $list]),
                        'url' => url('/tai-khoan/don-hang', array_filter(['tab' => $list['tab'] === 'all' ? null : $list['tab'],
                                                                         'page' => $list['page'] > 1 ? $list['page'] : null]))]);
    }

    private function orderList(Request $req): array
    {
        return AccountService::orders($this->uid(), (string) $req->query('tab', 'all'), (int) $req->query('page', 1));
    }

    public function order(Request $req): void
    {
        $order = AccountService::order($this->uid(), $req->params['code']);
        if (!$order) throw HttpException::notFound(t('account.orders.notFound'));
        $this->section($req, 'order', ['order' => $order], t('account.orders.detail', ['code' => $order['order_code']]));
    }

    public function addresses(Request $req): void
    {
        $this->section($req, 'addresses', ['addresses' => AccountService::addresses($this->uid())]);
    }

    public function measurements(Request $req): void
    {
        $this->section($req, 'measurements', ['profiles' => AccountService::measurements($this->uid())]);
    }

    public function reviews(Request $req): void
    {
        $this->section($req, 'reviews', ['toReview' => Reviews::toReview($this->uid(), lang()), 'mine' => Reviews::mine($this->uid(), lang())]);
    }

    public function profile(Request $req): void
    {
        $this->section($req, 'profile', []);
    }

    // ---------------------------------------------------------------- Ajax actions

    /** PUT /api/account/profile */
    public function updateProfile(Request $req): void
    {
        $name = AccountService::updateProfile($this->uid(), $req->body());
        Response::json(['ok' => true, 'message' => t('account.profile.saved'), 'hello' => t('account.hello', ['name' => $name])]);
    }

    /** POST /api/account/password */
    public function changePassword(Request $req): void
    {
        AccountService::changePassword($this->uid(), $req->body());
        Response::json(['ok' => true, 'message' => t('account.profile.changed')]);
    }

    /** POST /api/account/addresses  ·  PUT /api/account/addresses/{id} */
    public function saveAddress(Request $req): void
    {
        $id = isset($req->params['id']) ? (int) $req->params['id'] : null;
        AccountService::saveAddress($this->uid(), $id, $req->body());
        $this->addressesJson(t('account.addresses.saved'));
    }

    /** PATCH /api/account/addresses/{id}/default */
    public function defaultAddress(Request $req): void
    {
        AccountService::setDefaultAddress($this->uid(), (int) $req->params['id']);
        $this->addressesJson(t('account.addresses.defaultSet'));
    }

    /** DELETE /api/account/addresses/{id} */
    public function deleteAddress(Request $req): void
    {
        AccountService::deleteAddress($this->uid(), (int) $req->params['id']);
        $this->addressesJson(t('account.addresses.deleted'));
    }

    private function addressesJson(string $message): never
    {
        Response::json(['ok' => true, 'message' => $message,
                        'html' => View::partial('account/address-list', ['addresses' => AccountService::addresses($this->uid())])]);
    }

    /** POST /api/account/measurements  ·  PUT /api/account/measurements/{id} */
    public function saveMeasurement(Request $req): void
    {
        $id = isset($req->params['id']) ? (int) $req->params['id'] : null;
        $in = $req->body();
        $newId = AccountService::saveMeasurement($this->uid(), $id, $in);
        $profiles = AccountService::measurements($this->uid());
        Response::json(['ok' => true, 'id' => $newId,
            'message' => $req->query('from') === 'pdp'
                ? t('pdp.custom.savedProfile', ['name' => trim((string) ($in['name'] ?? ''))]) : t('account.measurements.saved'),
            'html' => View::partial('account/measurement-list', ['profiles' => $profiles]),
            'profiles' => array_map(fn($p) => ['id' => (int) $p['id'], 'name' => $p['name'], 'values' => AccountService::measurementValues($p)], $profiles),
        ]);
    }

    /** DELETE /api/account/measurements/{id} */
    public function deleteMeasurement(Request $req): void
    {
        AccountService::deleteMeasurement($this->uid(), (int) $req->params['id']);
        Response::json(['ok' => true, 'message' => t('account.measurements.deleted'),
                        'html' => View::partial('account/measurement-list', ['profiles' => AccountService::measurements($this->uid())])]);
    }

    /** POST /api/account/orders/{code}/cancel → re-rendered order detail */
    public function cancelOrder(Request $req): void
    {
        $code = $req->params['code'];
        AccountService::cancel($this->uid(), $code);
        $order = AccountService::order($this->uid(), $code);
        Response::json(['ok' => true, 'message' => t('account.orders.cancelled', ['code' => $code]),
                        'html' => View::partial('account/order', ['order' => $order])]);
    }

    // ---------------------------------------------------------------- rendering

    private function section(Request $req, string $section, array $data, ?string $title = null): never
    {
        [$template, $titleKey] = self::SECTIONS[$section];
        $title ??= t($titleKey);
        $body = View::partial($template, $data);
        if ($req->query('fragment') && $req->isAjax()) {
            Response::json(['html' => $body, 'title' => $title . ' · ' . t('brand'), 'section' => $section === 'order' ? 'orders' : $section]);
        }
        View::page('account/shell', ['title' => $title, 'body' => $body, 'section' => $section === 'order' ? 'orders' : $section]);
    }
}
