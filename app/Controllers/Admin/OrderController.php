<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{HttpException, Request, Response, View};
use App\Services\AdminOrderService as Orders;
use App\Support\Xlsx;

final class OrderController
{
    // ---------------------------------------------------------------- pages

    /** GET /admin/don-hang */
    public function index(Request $req): void
    {
        $filters = $this->filters($req);
        View::page('admin/orders/index', [
            'title'   => 'Đơn hàng',
            'filters' => $filters,
            'result'  => Orders::list($filters),
            'counts'  => Orders::statusCounts(),
        ], 200, 'admin/layout');
    }

    /** GET /admin/don-hang/{code} */
    public function show(Request $req): void
    {
        $order = Orders::find($req->params['code']);
        if (!$order) throw HttpException::notFound('Không tìm thấy đơn hàng');
        View::page('admin/orders/show', ['title' => 'Đơn ' . $order['order_code'], 'order' => $order], 200, 'admin/layout');
    }

    /** GET /admin/don-hang/in?codes=A,B&type=invoice|packing — printable page, one order per sheet */
    public function print(Request $req): void
    {
        $codes = array_filter(explode(',', (string) $req->query('codes', '')));
        $orders = Orders::findMany($codes);
        if (!$orders) throw HttpException::notFound('Không tìm thấy đơn hàng để in');
        $type = $req->query('type') === 'packing' ? 'packing' : 'invoice';

        header('Content-Type: text/html; charset=utf-8');
        echo View::partial('admin/orders/print', ['orders' => $orders, 'type' => $type]);
        exit;
    }

    /** GET /admin/don-hang/xuat-excel?{same filters as the list} — two sheets: orders, and items */
    public function export(Request $req): void
    {
        $f = $this->filters($req);
        $orders = Orders::exportRows($f);

        $orderRows = [];
        $itemRows = [];
        foreach ($orders as $o) {
            $qty = array_sum(array_column($o['items'], 'quantity'));
            $orderRows[] = [
                $o['order_code'], $o['created_at'], $o['customer_name'], $o['phone'], $o['email'], $o['shipping_address'],
                Orders::PAYMENT_METHODS[$o['payment_method']], Orders::PAYMENT_STATUSES[$o['payment_status']][0],
                Orders::STATUSES[$o['status']][0], $qty, (int) $o['subtotal'], $o['coupon_code'], (int) $o['discount'], (int) $o['shipping_fee'], (int) $o['total'], $o['note'],
            ];
            foreach ($o['items'] as $it) {
                $m = $it['custom_measurements'] ? json_decode($it['custom_measurements'], true) : null;
                $itemRows[] = [
                    $o['order_code'], $o['created_at'], Orders::STATUSES[$o['status']][0], $it['product_name'],
                    $it['size'] === 'CUSTOM' ? 'May đo' : $it['size'], $it['fabric_name'], $it['color'],
                    (int) $it['quantity'], (int) $it['unit_price'], (int) $it['unit_price'] * (int) $it['quantity'],
                    $m['height'] ?? null, $m['weight'] ?? null, $m['bust'] ?? null, $m['waist'] ?? null, $m['hip'] ?? null, $m['note'] ?? null,
                ];
            }
        }

        (new Xlsx())
            ->addSheet('Đơn hàng', [
                ['Mã đơn', 18, 'string'], ['Ngày đặt', 17, 'date'], ['Khách hàng', 22, 'string'], ['SĐT', 13, 'string'],
                ['Email', 22, 'string'], ['Địa chỉ', 40, 'string'], ['Thanh toán', 13, 'string'], ['TT thanh toán', 16, 'string'],
                ['Trạng thái', 14, 'string'], ['Số SP', 7, 'int'], ['Tạm tính', 13, 'money'], ['Mã giảm giá', 14, 'string'], ['Giảm giá', 12, 'money'], ['Phí ship', 10, 'money'],
                ['Tổng', 13, 'money'], ['Ghi chú', 30, 'wrap'],
            ], $orderRows)
            ->addSheet('Chi tiết sản phẩm', [
                ['Mã đơn', 18, 'string'], ['Ngày đặt', 17, 'date'], ['Trạng thái', 14, 'string'], ['Sản phẩm', 32, 'string'],
                ['Size', 8, 'string'], ['Chất liệu', 12, 'string'], ['Màu', 12, 'string'], ['SL', 6, 'int'],
                ['Đơn giá', 13, 'money'], ['Thành tiền', 13, 'money'], ['Cao (cm)', 9, 'int'], ['Nặng (kg)', 9, 'int'],
                ['Ngực', 7, 'int'], ['Eo', 7, 'int'], ['Mông', 7, 'int'], ['Ghi chú may', 30, 'wrap'],
            ], $itemRows)
            ->download('don-hang-' . date('Ymd-His') . '.xlsx');
    }

    // ---------------------------------------------------------------- Ajax

    /** GET /admin/api/orders — Ajax rows */
    public function rows(Request $req): void
    {
        $filters = $this->filters($req);
        $result = Orders::list($filters);
        Response::json([
            'html'   => View::partial('admin/orders/rows', ['result' => $result, 'filters' => $filters]),
            'total'  => $result['total'],
            'counts' => Orders::statusCounts(),
        ]);
    }

    /** PATCH /admin/api/orders/{code}  {status?, payment_status?} */
    public function update(Request $req): void
    {
        $in = array_filter([
            'status'         => $req->input('status'),
            'payment_status' => $req->input('payment_status'),
        ], fn($v) => $v !== null);
        $r = Orders::update($req->params['code'], $in);
        $this->detailResponse($req->params['code'], 'Đã cập nhật đơn hàng', [
            'status' => $r['status'],
            'final'  => $r['status'] === 'cancelled',
            'badge'  => View::partial('admin/partials/badge', ['map' => Orders::STATUSES, 'key' => $r['status']]),
            'payment_badge' => View::partial('admin/partials/badge', ['map' => Orders::PAYMENT_STATUSES, 'key' => $r['payment_status']]),
        ]);
    }

    /** POST /admin/api/orders/bulk  {codes: [], status} */
    public function bulk(Request $req): void
    {
        $r = Orders::bulkUpdate((array) $req->input('codes', []), (string) $req->input('status', ''));
        $msg = "Đã cập nhật {$r['updated']} đơn";
        if ($r['skipped']) $msg .= ' · bỏ qua ' . count($r['skipped']);
        Response::json(['ok' => true, 'message' => $msg, 'skipped' => $r['skipped']]);
    }

    /** POST /admin/api/orders/{code}/notes  {note} */
    public function addNote(Request $req): void
    {
        Orders::addNote($req->params['code'], (string) $req->input('note', ''));
        $this->detailResponse($req->params['code'], 'Đã thêm ghi chú');
    }

    /** PATCH /admin/api/orders/{code}/customer  {customer_name, phone, email, shipping_address} */
    public function updateCustomer(Request $req): void
    {
        Orders::updateCustomer($req->params['code'], $req->body());
        $this->detailResponse($req->params['code'], 'Đã lưu thông tin giao hàng');
    }

    /** GET /admin/api/notifications — pending orders for the bell (polled) */
    public function notifications(Request $req): void
    {
        $s = Orders::pendingSummary();
        Response::json([
            'count'     => $s['count'],
            'latest_id' => $s['latest_id'],
            'html'      => View::partial('admin/partials/notifications', ['summary' => $s]),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    /** Every change on the detail page returns the fresh timeline and customer card. */
    private function detailResponse(string $code, string $message, array $extra = []): never
    {
        $order = Orders::find($code);
        Response::json(array_merge($extra, [
            'ok'            => true,
            'message'       => $message,
            'timeline_html' => View::partial('admin/orders/timeline', ['order' => $order]),
            'customer_html' => View::partial('admin/orders/customer', ['order' => $order]),
        ]));
    }

    private function filters(Request $req): array
    {
        $date = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        return [
            'q'      => mb_substr(trim((string) $req->query('q', '')), 0, 60),
            'status' => (string) $req->query('status', ''),
            'from'   => $date($req->query('from')),
            'to'     => $date($req->query('to')),
            'page'   => (int) $req->query('page', 1),
        ];
    }
}
