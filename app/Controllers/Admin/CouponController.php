<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{HttpException, Request, Response, View};
use App\Services\Coupons;

final class CouponController
{
    /** GET /admin/khuyen-mai */
    public function index(Request $req): void
    {
        $f = $this->filters($req);
        View::page('admin/coupons/index', ['title' => 'Mã giảm giá', 'filters' => $f, 'result' => Coupons::list($f)], 200, 'admin/layout');
    }

    /** GET /admin/api/coupons — Ajax rows */
    public function rows(Request $req): void
    {
        $f = $this->filters($req);
        Response::json(['html' => View::partial('admin/coupons/rows', ['result' => Coupons::list($f), 'filters' => $f])]);
    }

    /** GET /admin/khuyen-mai/them */
    public function create(Request $req): void
    {
        $this->form(['code' => Coupons::generateCode(), 'type' => 'percent', 'value' => 10, 'min_order' => 0, 'is_active' => 1]);
    }

    /** GET /admin/khuyen-mai/{id} */
    public function edit(Request $req): void
    {
        $c = Coupons::find((int) $req->params['id']);
        if (!$c) throw HttpException::notFound('Không tìm thấy mã giảm giá');
        $this->form($c);
    }

    /** POST /admin/api/coupons · PUT /admin/api/coupons/{id} */
    public function save(Request $req): void
    {
        $id = isset($req->params['id']) ? (int) $req->params['id'] : null;
        $newId = Coupons::save($id, $req->body());
        Response::json(['ok' => true, 'message' => $id ? 'Đã lưu mã giảm giá' : 'Đã tạo mã giảm giá',
                        'redirect' => $id ? null : url('/admin/khuyen-mai/' . $newId)]);
    }

    /** DELETE /admin/api/coupons/{id} */
    public function delete(Request $req): void
    {
        $msg = Coupons::delete((int) $req->params['id']);
        Response::json(['ok' => true, 'message' => $msg]);
    }

    /** GET /admin/api/coupons/generate — a fresh random code */
    public function generate(Request $req): void
    {
        Response::json(['code' => Coupons::generateCode()]);
    }

    private function form(array $coupon): never
    {
        View::page('admin/coupons/form', [
            'title'  => isset($coupon['id']) ? 'Mã ' . $coupon['code'] : 'Tạo mã giảm giá',
            'coupon' => $coupon,
        ], 200, 'admin/layout');
    }

    private function filters(Request $req): array
    {
        return [
            'q'      => mb_substr(trim((string) $req->query('q', '')), 0, 40),
            'status' => (string) $req->query('status', ''),
            'page'   => (int) $req->query('page', 1),
        ];
    }
}
