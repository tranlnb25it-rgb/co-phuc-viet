<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{HttpException, Request, Response, View};
use App\Services\CustomerService as Customers;

final class CustomerController
{
    /** GET /admin/khach-hang */
    public function index(Request $req): void
    {
        $f = $this->filters($req);
        View::page('admin/customers/index', ['title' => 'Khách hàng', 'filters' => $f, 'result' => Customers::list($f)], 200, 'admin/layout');
    }

    /** GET /admin/api/customers — Ajax rows */
    public function rows(Request $req): void
    {
        $f = $this->filters($req);
        Response::json(['html' => View::partial('admin/customers/rows', ['result' => Customers::list($f), 'filters' => $f])]);
    }

    /** GET /admin/khach-hang/{phone} */
    public function show(Request $req): void
    {
        $c = Customers::find($req->params['phone']);
        if (!$c) throw HttpException::notFound('Không tìm thấy khách hàng');
        View::page('admin/customers/show', ['title' => $c['name'], 'c' => $c], 200, 'admin/layout');
    }

    private function filters(Request $req): array
    {
        return [
            'q'       => mb_substr(trim((string) $req->query('q', '')), 0, 60),
            'segment' => (string) $req->query('segment', ''),
            'sort'    => (string) $req->query('sort', 'last'),
            'page'    => (int) $req->query('page', 1),
        ];
    }
}
