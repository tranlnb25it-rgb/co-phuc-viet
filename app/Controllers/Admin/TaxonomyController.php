<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{HttpException, Request, Response, View};
use App\Services\TaxonomyService as Taxonomy;

final class TaxonomyController
{
    /** GET /admin/danh-muc?tab=categories|dynasties|fabrics */
    public function index(Request $req): void
    {
        $tab = isset(Taxonomy::TYPES[$req->query('tab', '')]) ? $req->query('tab') : 'categories';
        View::page('admin/taxonomy/index', ['title' => 'Danh mục & thuộc tính', 'tab' => $tab, 'lists' => Taxonomy::all()], 200, 'admin/layout');
    }

    /** POST /admin/api/taxonomy/{type}  (create)  ·  PUT /admin/api/taxonomy/{type}/{id}  (update) */
    public function save(Request $req): void
    {
        $type = $req->params['type'];
        $id = isset($req->params['id']) ? (int) $req->params['id'] : null;
        Taxonomy::save($type, $id, $req->body());
        $this->respond($type, $id ? 'Đã lưu' : 'Đã thêm');
    }

    /** DELETE /admin/api/taxonomy/{type}/{id} */
    public function delete(Request $req): void
    {
        $type = $req->params['type'];
        Taxonomy::delete($type, (int) $req->params['id']);
        $this->respond($type, 'Đã xoá');
    }

    private function respond(string $type, string $message): never
    {
        if (!isset(Taxonomy::TYPES[$type])) throw HttpException::notFound();
        Response::json(['ok' => true, 'message' => $message,
                        'html' => View::partial("admin/taxonomy/$type", ['lists' => Taxonomy::all()])]);
    }
}
