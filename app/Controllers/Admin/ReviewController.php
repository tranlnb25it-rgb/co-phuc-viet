<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{HttpException, Request, Response, View};
use App\Services\Reviews;

/** /admin/danh-gia — moderate product reviews: approve / hide, reply publicly, delete. */
final class ReviewController
{
    /** GET /admin/danh-gia */
    public function index(Request $req): void
    {
        $f = $this->filters($req);
        View::page('admin/reviews/index', ['title' => 'Đánh giá sản phẩm', 'filters' => $f,
                                           'result' => Reviews::adminList($f), 'counts' => $this->counts()], 200, 'admin/layout');
    }

    /** GET /admin/api/reviews — Ajax list */
    public function rows(Request $req): void
    {
        $f = $this->filters($req);
        Response::json(['html' => View::partial('admin/reviews/rows', ['result' => Reviews::adminList($f), 'filters' => $f]),
                        'counts' => $this->counts()]);
    }

    /** PATCH /admin/api/reviews/{id}  {status: approved|rejected} → re-rendered card */
    public function status(Request $req): void
    {
        $id = (int) $req->params['id'];
        $status = (string) $req->input('status', '');
        Reviews::setStatus([$id], $status);
        $this->card($id, $status === 'approved' ? 'Đã duyệt — đánh giá đang hiển thị' : 'Đã ẩn đánh giá');
    }

    /** POST /admin/api/reviews/bulk  {ids: [], status} */
    public function bulk(Request $req): void
    {
        $status = (string) $req->input('status', '');
        $n = Reviews::setStatus((array) $req->input('ids', []), $status);
        Response::json(['ok' => true, 'message' => ($status === 'approved' ? 'Đã duyệt ' : 'Đã ẩn ') . "$n đánh giá", 'counts' => $this->counts()]);
    }

    /** PUT /admin/api/reviews/{id}/reply  {reply} */
    public function reply(Request $req): void
    {
        $id = (int) $req->params['id'];
        $text = (string) $req->input('reply', '');
        Reviews::reply($id, $text);
        $this->card($id, trim($text) === '' ? 'Đã xoá phản hồi' : 'Đã đăng phản hồi');
    }

    /** DELETE /admin/api/reviews/{id} */
    public function delete(Request $req): void
    {
        Reviews::delete((int) $req->params['id']);
        Response::json(['ok' => true, 'message' => 'Đã xoá đánh giá', 'counts' => $this->counts()]);
    }

    private function card(int $id, string $message): never
    {
        $r = Reviews::find($id);
        if (!$r) throw HttpException::notFound('Không tìm thấy đánh giá');
        Response::json(['ok' => true, 'message' => $message, 'counts' => $this->counts(),
                        'html' => View::partial('admin/reviews/card', ['r' => $r])]);
    }

    /** Status counts keyed like the filter tabs' data-count ("all" for the total). */
    private function counts(): array
    {
        $c = Reviews::counts();
        $c['all'] = $c[''];
        unset($c['']);
        return $c;
    }

    private function filters(Request $req): array
    {
        return [
            'status' => (string) $req->query('status', ''),
            'rating' => (string) $req->query('rating', ''),
            'q'      => mb_substr(trim((string) $req->query('q', '')), 0, 60),
            'page'   => (int) $req->query('page', 1),
        ];
    }
}
