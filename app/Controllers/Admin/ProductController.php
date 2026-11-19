<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database, HttpException, Request, Response, View};
use App\Services\AdminProductService as Products;

final class ProductController
{
    private const LAYOUT = 'admin/layout';

    // ---------------------------------------------------------------- pages

    /** GET /admin/san-pham */
    public function index(Request $req): void
    {
        $filters = $this->filters($req);
        View::page('admin/products/index', [
            'title'      => 'Sản phẩm',
            'filters'    => $filters,
            'result'     => Products::list($filters),
            'categories' => Products::categoryOptions(),
        ], 200, self::LAYOUT);
    }

    /** GET /admin/api/products — Ajax rows for the list page */
    public function rows(Request $req): void
    {
        $filters = $this->filters($req);
        $result = Products::list($filters);
        Response::json([
            'html'  => View::partial('admin/products/rows', ['result' => $result, 'filters' => $filters]),
            'total' => $result['total'],
        ]);
    }

    /** GET /admin/san-pham/them */
    public function create(Request $req): void
    {
        $this->form(['is_active' => 1, 'allow_custom_tailoring' => 1, 'gender' => 'unisex', 'custom_tailoring_fee' => 0]);
    }

    /** GET /admin/san-pham/{id} */
    public function edit(Request $req): void
    {
        $p = Products::find($this->id($req));
        if (!$p) throw HttpException::notFound('Không tìm thấy sản phẩm');
        $this->form($p);
    }

    // ---------------------------------------------------------------- product (Ajax)

    /** POST /admin/api/products */
    public function store(Request $req): void
    {
        [$data, $errors] = Products::validate($req->body());
        if ($errors) throw HttpException::badRequest('Vui lòng kiểm tra lại thông tin', $errors);
        $id = Products::create($data);
        Response::json(['ok' => true, 'id' => $id, 'redirect' => url('/admin/san-pham/' . $id, ['created' => 1])]);
    }

    /** PUT /admin/api/products/{id} */
    public function update(Request $req): void
    {
        $id = $this->existing($req);
        [$data, $errors] = Products::validate($req->body(), $id);
        if ($errors) throw HttpException::badRequest('Vui lòng kiểm tra lại thông tin', $errors);
        Products::update($id, $data);
        Response::json(['ok' => true, 'message' => 'Đã lưu sản phẩm', 'slug' => $data['slug'],
                        'view_url' => url('/san-pham/' . $data['slug'])]);
    }

    /** DELETE /admin/api/products/{id} */
    public function destroy(Request $req): void
    {
        Products::delete($this->existing($req));
        Response::json(['ok' => true, 'message' => 'Đã xoá sản phẩm']);
    }

    /** PATCH /admin/api/products/{id}/toggle  {field: is_active|is_featured} */
    public function toggle(Request $req): void
    {
        $value = Products::toggle($this->existing($req), (string) $req->input('field'));
        Response::json(['ok' => true, 'value' => $value]);
    }

    // ---------------------------------------------------------------- variants (Ajax)

    /** POST /admin/api/products/{id}/variants */
    public function generateVariants(Request $req): void
    {
        $id = $this->existing($req);
        $created = Products::generateVariants($id, $req->body());
        $this->variantsResponse($id, $created ? "Đã tạo $created biến thể" : 'Các biến thể này đã tồn tại');
    }

    /** PATCH /admin/api/variants/{id}  {stock} */
    public function updateVariant(Request $req): void
    {
        $v = Products::updateVariant($this->id($req), $req->body());
        Response::json(['ok' => true, 'stock' => $v['stock'], 'low' => $v['stock'] <= low_stock(),
                        'total' => (int) Database::one('SELECT SUM(stock) s FROM product_variants WHERE product_id = ?', [$v['product_id']])['s']]);
    }

    /** DELETE /admin/api/variants/{id} */
    public function deleteVariant(Request $req): void
    {
        $productId = Products::deleteVariant($this->id($req));
        $this->variantsResponse($productId, 'Đã xoá biến thể');
    }

    // ---------------------------------------------------------------- images (Ajax)

    /** POST /admin/api/products/{id}/images  multipart: images[], view_type */
    public function uploadImages(Request $req): void
    {
        $id = $this->existing($req);
        if (empty($_FILES['images']['name'][0])) {
            // an empty $_FILES on a non-empty request usually means post_max_size was exceeded
            throw HttpException::badRequest('Chọn ít nhất một ảnh (tối đa 5MB mỗi ảnh)');
        }
        $r = Products::uploadImages($id, $_FILES['images'], (string) $req->input('view_type', 'front'));
        Response::json([
            'ok'      => $r['saved'] > 0,
            'message' => $r['saved'] ? "Đã tải lên {$r['saved']} ảnh" : 'Không có ảnh nào được lưu',
            'errors'  => $r['errors'],
            'html'    => View::partial('admin/products/images', ['product' => Products::find($id)]),
        ], $r['saved'] ? 200 : 422);
    }

    /** DELETE /admin/api/images/{id} */
    public function deleteImage(Request $req): void
    {
        $productId = Products::deleteImage($this->id($req));
        Response::json(['ok' => true, 'message' => 'Đã xoá ảnh',
                        'html' => View::partial('admin/products/images', ['product' => Products::find($productId)])]);
    }

    /** POST /admin/api/products/{id}/duplicate */
    public function duplicate(Request $req): void
    {
        $newId = Products::duplicate($this->existing($req));
        Response::json(['ok' => true, 'id' => $newId, 'redirect' => url('/admin/san-pham/' . $newId, ['duplicated' => 1])]);
    }

    /** POST /admin/api/products/bulk  {ids: [], action: activate|hide|feature|unfeature|delete} */
    public function bulk(Request $req): void
    {
        $action = (string) $req->input('action', '');
        $n = Products::bulk((array) $req->input('ids', []), $action,
                             ['percent' => $req->input('percent'), 'ends_at' => $req->input('ends_at')]);
        Response::json(['ok' => true, 'message' => "Đã cập nhật $n sản phẩm"]);
    }

    /** PATCH /admin/api/products/{id}/images/order  {ids: []} */
    public function reorderImages(Request $req): void
    {
        $id = $this->existing($req);
        Products::reorderImages($id, (array) $req->input('ids', []));
        Response::json(['ok' => true, 'message' => 'Đã lưu thứ tự ảnh']);
    }

    /** PATCH /admin/api/images/{id}  {view_type} */
    public function setImageType(Request $req): void
    {
        $productId = Products::setImageType($this->id($req), (string) $req->input('view_type', ''));
        Response::json(['ok' => true, 'message' => 'Đã đổi loại ảnh',
                        'html' => View::partial('admin/products/images', ['product' => Products::find($productId)])]);
    }

    // ---------------------------------------------------------------- helpers

    private function form(array $product): never
    {
        View::page('admin/products/form', [
            'title'      => isset($product['id']) ? 'Sửa: ' . $product['name_vi'] : 'Thêm sản phẩm',
            'product'    => $product,
            'categories' => Products::categoryOptions(),
            'dynasties'  => Database::all('SELECT id, name_vi FROM dynasties ORDER BY sort_order'),
            'fabrics'    => Database::all('SELECT id, name_vi, price_modifier FROM fabrics ORDER BY id'),
        ], 200, self::LAYOUT);
    }

    private function variantsResponse(int $productId, string $message): never
    {
        $fabrics = Database::all('SELECT id, name_vi, price_modifier FROM fabrics ORDER BY id');
        Response::json(['ok' => true, 'message' => $message,
                        'html' => View::partial('admin/products/variants', ['product' => Products::find($productId), 'fabrics' => $fabrics])]);
    }

    private function filters(Request $req): array
    {
        return [
            'q'        => mb_substr(trim((string) $req->query('q', '')), 0, 100),
            'category' => (int) $req->query('category', 0) ?: null,
            'status'   => (string) $req->query('status', ''),
            'page'     => (int) $req->query('page', 1),
        ];
    }

    private function id(Request $req): int
    {
        $id = $req->params['id'] ?? '';
        if (!ctype_digit((string) $id)) throw HttpException::notFound();
        return (int) $id;
    }

    private function existing(Request $req): int
    {
        $id = $this->id($req);
        if (!Database::one('SELECT id FROM products WHERE id = ?', [$id])) throw HttpException::notFound('Không tìm thấy sản phẩm');
        return $id;
    }
}
