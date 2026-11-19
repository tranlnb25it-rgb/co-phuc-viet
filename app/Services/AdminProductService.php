<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/** Admin CRUD for products, their variants (stock) and images. Admin text is Vietnamese. */
final class AdminProductService
{
    public const GENDERS = ['nam' => 'Nam', 'nu' => 'Nữ', 'unisex' => 'Unisex'];
    public const SIZES = ['S', 'M', 'L', 'XL', 'FREE'];
    public const VIEW_TYPES = ['front' => 'Mặt trước', 'back' => 'Mặt sau', 'detail' => 'Chi tiết'];
    private const MAX_UPLOAD = 5 * 1024 * 1024;
    private const MIME_EXT = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const PER_PAGE = 15;

    // ---------------------------------------------------------------- list

    public static function list(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(p.name_vi LIKE :q1 OR p.name_en LIKE :q2 OR p.slug LIKE :q3)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($f['category'])) {
            $where[] = '(p.category_id = :cat OR c.parent_id = :cat2)';
            $params['cat'] = $params['cat2'] = (int) $f['category'];
        }
        match ($f['status'] ?? '') {
            'active'   => $where[] = 'p.is_active = 1',
            'hidden'   => $where[] = 'p.is_active = 0',
            'featured' => $where[] = 'p.is_featured = 1',
            'sale'     => $where[] = \App\Support\Pricing::SQL_ON_SALE,
            'low'      => $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.stock <= ' . low_stock() . ')',
            default    => null,
        };
        $sql = 'FROM products p JOIN categories c ON c.id = p.category_id LEFT JOIN dynasties d ON d.id = p.dynasty_id
                WHERE ' . implode(' AND ', $where);

        $total = (int) Database::one("SELECT COUNT(*) n $sql", $params)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT p.id, p.slug, p.name_vi, p.base_price, p.sale_price, p.sale_starts_at, p.sale_ends_at, p.gender, p.is_active, p.is_featured, p.allow_custom_tailoring,
                    c.name_vi AS category, d.name_vi AS dynasty,
                    (SELECT url FROM product_images i WHERE i.product_id = p.id ORDER BY FIELD(i.view_type,'front','back','detail'), i.sort_order LIMIT 1) AS image,
                    (SELECT color_hex FROM product_variants v WHERE v.product_id = p.id ORDER BY v.id LIMIT 1) AS tint,
                    (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.id) AS stock,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS variant_count,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.stock <= " . low_stock() . ") AS low_count
             $sql ORDER BY p.updated_at DESC, p.id DESC LIMIT " . self::PER_PAGE . " OFFSET $offset", $params);

        return compact('items', 'total', 'page', 'pages');
    }

    // ---------------------------------------------------------------- read

    public static function find(int $id): ?array
    {
        $p = Database::one('SELECT * FROM products WHERE id = ?', [$id]);
        if (!$p) return null;
        $p['images'] = self::images($id);
        $p['variants'] = self::variants($id);
        return $p;
    }

    public static function images(int $id): array
    {
        return Database::all("SELECT * FROM product_images WHERE product_id = ?
                              ORDER BY FIELD(view_type,'front','back','detail'), sort_order, id", [$id]);
    }

    public static function variants(int $id): array
    {
        return Database::all("SELECT v.*, f.name_vi AS fabric_name FROM product_variants v
                              LEFT JOIN fabrics f ON f.id = v.fabric_id
                              WHERE v.product_id = ?
                              ORDER BY f.id IS NULL, f.id, v.color_hex, FIELD(v.size,'S','M','L','XL','FREE')", [$id]);
    }

    /** Category options as a flat list with indented children. */
    public static function categoryOptions(): array
    {
        $rows = Database::all('SELECT id, parent_id, name_vi FROM categories ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, sort_order');
        return array_map(fn($r) => ['id' => (int) $r['id'], 'label' => ($r['parent_id'] ? '— ' : '') . $r['name_vi']], $rows);
    }

    // ---------------------------------------------------------------- write: product

    /** @return array{0: array, 1: array<string,string>} [clean columns, errors] */
    public static function validate(array $in, ?int $id = null): array
    {
        $str = fn(string $k, int $max) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
        $int = fn(string $k) => (int) preg_replace('/\D/', '', (string) ($in[$k] ?? '0'));

        $d = [
            'name_vi'               => $str('name_vi', 160),
            'name_en'               => $str('name_en', 160),
            'slug'                  => slugify($str('slug', 160) ?: $str('name_vi', 160)),
            'category_id'           => (int) ($in['category_id'] ?? 0),
            'dynasty_id'            => ($in['dynasty_id'] ?? '') === '' ? null : (int) $in['dynasty_id'],
            'gender'                => (string) ($in['gender'] ?? 'unisex'),
            'base_price'            => $int('base_price'),
            'custom_tailoring_fee'  => $int('custom_tailoring_fee'),
            'sale_price'            => trim((string) ($in['sale_price'] ?? '')) === '' ? null : $int('sale_price'),
            'sale_starts_at'        => self::datetime($in['sale_starts_at'] ?? ''),
            'sale_ends_at'          => self::datetime($in['sale_ends_at'] ?? ''),
            'allow_custom_tailoring'=> !empty($in['allow_custom_tailoring']) ? 1 : 0,
            'is_featured'           => !empty($in['is_featured']) ? 1 : 0,
            'is_active'             => !empty($in['is_active']) ? 1 : 0,
            'short_description_vi'  => $str('short_description_vi', 500) ?: null,
            'short_description_en'  => $str('short_description_en', 500) ?: null,
            'historical_context_vi' => $str('historical_context_vi', 5000) ?: null,
            'historical_context_en' => $str('historical_context_en', 5000) ?: null,
        ];
        if ($d['name_en'] === '') $d['name_en'] = $d['name_vi'];

        $e = [];
        if (mb_strlen($d['name_vi']) < 2) $e['name_vi'] = 'Nhập tên sản phẩm';
        if ($d['slug'] === '') $e['slug'] = 'Slug không hợp lệ';
        elseif (Database::one('SELECT id FROM products WHERE slug = ? AND id <> ?', [$d['slug'], $id ?? 0])) $e['slug'] = 'Slug đã được dùng cho sản phẩm khác';
        if (!Database::one('SELECT id FROM categories WHERE id = ?', [$d['category_id']])) $e['category_id'] = 'Chọn danh mục';
        if ($d['dynasty_id'] !== null && !Database::one('SELECT id FROM dynasties WHERE id = ?', [$d['dynasty_id']])) $e['dynasty_id'] = 'Triều đại không hợp lệ';
        if (!isset(self::GENDERS[$d['gender']])) $e['gender'] = 'Chọn giới tính';
        if ($d['base_price'] < 1000 || $d['base_price'] > 1_000_000_000) $e['base_price'] = 'Giá từ 1.000 ₫ đến 1 tỷ ₫';
        if ($d['custom_tailoring_fee'] > 100_000_000) $e['custom_tailoring_fee'] = 'Phí may đo quá lớn';
        if ($d['sale_price'] !== null && ($d['sale_price'] < 1000 || $d['sale_price'] >= $d['base_price'])) $e['sale_price'] = 'Giá khuyến mãi phải thấp hơn giá gốc';
        if ($d['sale_starts_at'] === 'invalid') $e['sale_starts_at'] = 'Ngày giờ không hợp lệ';
        if ($d['sale_ends_at'] === 'invalid') $e['sale_ends_at'] = 'Ngày giờ không hợp lệ';
        if (!isset($e['sale_starts_at']) && !isset($e['sale_ends_at']) && $d['sale_starts_at'] && $d['sale_ends_at'] && $d['sale_ends_at'] <= $d['sale_starts_at']) $e['sale_ends_at'] = 'Phải sau thời điểm bắt đầu';
        if ($d['sale_price'] === null) { $d['sale_starts_at'] = null; $d['sale_ends_at'] = null; }   // no sale → no schedule
        return [$d, $e];
    }

    /** "2026-11-20T08:00" → "2026-11-20 08:00:00", '' → null, garbage → 'invalid' */
    private static function datetime(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') return null;
        $ts = strtotime(str_replace('T', ' ', $v));
        return $ts ? date('Y-m-d H:i:00', $ts) : 'invalid';
    }

    public static function create(array $d): int
    {
        $cols = array_keys($d);
        Database::exec('INSERT INTO products (' . implode(',', $cols) . ') VALUES (:' . implode(',:', $cols) . ')', $d);
        $id = (int) Database::pdo()->lastInsertId();
        if (!str_ends_with($d['name_vi'], '(bản sao)')) Activity::log('product.create', "Tạo sản phẩm “{$d['name_vi']}”", 'product', $id);
        return $id;
    }

    public static function update(int $id, array $d): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($d)));
        Database::exec("UPDATE products SET $set WHERE id = :id", $d + ['id' => $id]);
        Activity::log('product.update', "Sửa sản phẩm “{$d['name_vi']}”", 'product', $id);
    }

    public static function toggle(int $id, string $field): int
    {
        if (!in_array($field, ['is_active', 'is_featured'], true)) throw HttpException::badRequest('Trường không hợp lệ');
        Database::exec("UPDATE products SET $field = 1 - $field WHERE id = ?", [$id]);
        $r = Database::one("SELECT $field v, name_vi FROM products WHERE id = ?", [$id]);
        $what = $field === 'is_active' ? ($r['v'] ? 'Bật bán' : 'Ẩn') : ($r['v'] ? 'Đặt nổi bật' : 'Bỏ nổi bật');
        Activity::log('product.update', "$what “{$r['name_vi']}”", 'product', $id);
        return (int) $r['v'];
    }

    /** Hard delete. Past orders keep their snapshot (order_items.product_id becomes NULL). */
    public static function delete(int $id): void
    {
        $name = Database::one('SELECT name_vi FROM products WHERE id = ?', [$id])['name_vi'] ?? "#$id";
        foreach (self::images($id) as $img) self::unlinkUpload($img['url']);
        Database::exec('DELETE FROM products WHERE id = ?', [$id]);
        Activity::log('product.delete', "Xoá sản phẩm “{$name}”", 'product', $id);
    }

    // ---------------------------------------------------------------- write: variants

    /**
     * Create every size × fabric combination for one colour (existing combinations are skipped).
     * @return int number of variants created
     */
    public static function generateVariants(int $productId, array $in): int
    {
        $sizes = array_values(array_intersect(self::SIZES, (array) ($in['sizes'] ?? [])));
        $fabricIds = array_map('intval', (array) ($in['fabrics'] ?? []));
        $hex = strtoupper(trim((string) ($in['color_hex'] ?? '')));
        $colorVi = mb_substr(trim((string) ($in['color_vi'] ?? '')), 0, 60);
        $colorEn = mb_substr(trim((string) ($in['color_en'] ?? '')), 0, 60) ?: $colorVi;
        $stock = max(0, min(100000, (int) ($in['stock'] ?? 0)));

        $e = [];
        if (!$sizes) $e['sizes'] = 'Chọn ít nhất một size';
        if (!preg_match('/^#[0-9A-F]{6}$/', $hex)) $e['color_hex'] = 'Mã màu dạng #RRGGBB';
        if ($colorVi === '') $e['color_vi'] = 'Nhập tên màu';
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        $fabrics = $fabricIds
            ? Database::all('SELECT id, slug FROM fabrics WHERE id IN (' . implode(',', $fabricIds) . ')')
            : [['id' => null, 'slug' => 'na']];

        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO product_variants (product_id, size, fabric_id, color_vi, color_en, color_hex, stock, sku)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $created = 0;
        foreach ($fabrics as $f) {
            foreach ($sizes as $size) {
                $sku = sprintf('CP%03d-%s-%s-%s', $productId, $size, strtoupper(substr($f['slug'], 0, 3)), substr($hex, 1));
                $stmt->execute([$productId, $size, $f['id'], $colorVi, $colorEn, $hex, $stock, $sku]);
                if ($stmt->rowCount()) {
                    $created++;
                    if ($stock > 0) Inventory::record((int) Database::pdo()->lastInsertId(), $stock, 'initial', 'Tạo biến thể');
                }
            }
        }
        if ($created) Activity::log('product.variants', "Tạo $created biến thể màu $colorVi", 'product', $productId);
        return $created;
    }

    /** Set the stock of one variant (logged as a stock-take unless another manual reason is given). */
    public static function updateVariant(int $id, array $in): array
    {
        $reason = in_array($in['reason'] ?? '', Inventory::MANUAL, true) ? $in['reason'] : 'adjust';
        $stock = (string) ($in['stock'] ?? '');
        if (!preg_match('/^\d{1,6}$/', $stock)) throw HttpException::badRequest('Tồn kho từ 0 đến 100000', ['stock' => 'Tồn kho từ 0 đến 100000']);
        return Inventory::adjust($id, (int) $stock, null, $reason, $in['note'] ?? 'Sửa trong trang sản phẩm');
    }

    public static function deleteVariant(int $id): int
    {
        $v = Database::one('SELECT product_id, sku, stock FROM product_variants WHERE id = ?', [$id]);
        if (!$v) throw HttpException::notFound('Không tìm thấy biến thể');
        if ((int) $v['stock'] > 0) {
            Database::exec('UPDATE product_variants SET stock = 0 WHERE id = ?', [$id]);   // so the log shows "còn 0"
            Inventory::record($id, -(int) $v['stock'], 'adjust', 'Xoá biến thể');
        }
        Database::exec('DELETE FROM product_variants WHERE id = ?', [$id]);
        Activity::log('product.variants', "Xoá biến thể {$v['sku']}", 'product', $v['product_id']);
        return (int) $v['product_id'];
    }

    // ---------------------------------------------------------------- write: images

    /**
     * Store uploaded images under uploads/products/. Only real JPEG/PNG/WebP files (checked by content) are kept.
     * @return array{saved:int, errors:list<string>}
     */
    public static function uploadImages(int $productId, array $files, string $viewType): array
    {
        if (!isset(self::VIEW_TYPES[$viewType])) $viewType = 'detail';
        $dir = dirname(__DIR__, 2) . '/uploads/products';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        // normalise $_FILES['images'] (multiple) into a list
        $list = [];
        foreach ((array) ($files['name'] ?? []) as $i => $name) {
            $list[] = ['name' => $name, 'tmp' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $saved = 0; $errors = [];
        $sort = (int) (Database::one('SELECT COALESCE(MAX(sort_order), -1) m FROM product_images WHERE product_id = ?', [$productId])['m']);
        foreach ($list as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp'])) { $errors[] = "{$f['name']}: tải lên thất bại"; continue; }
            if ($f['size'] > self::MAX_UPLOAD) { $errors[] = "{$f['name']}: quá 5MB"; continue; }
            $ext = self::MIME_EXT[$finfo->file($f['tmp'])] ?? null;
            if (!$ext) { $errors[] = "{$f['name']}: chỉ nhận JPG, PNG, WebP"; continue; }

            $file = sprintf('%d-%s.%s', $productId, bin2hex(random_bytes(6)), $ext);
            if (!move_uploaded_file($f['tmp'], "$dir/$file")) { $errors[] = "{$f['name']}: không lưu được"; continue; }
            Database::exec('INSERT INTO product_images (product_id, url, view_type, sort_order) VALUES (?, ?, ?, ?)',
                           [$productId, "uploads/products/$file", $viewType, ++$sort]);
            $saved++;
        }
        // replace seed placeholders of the same view once real photos exist
        if ($saved) {
            Activity::log('product.images', "Tải lên $saved ảnh", 'product', $productId);
            Database::exec("DELETE FROM product_images WHERE product_id = ? AND view_type = ? AND url LIKE 'placeholder:%'", [$productId, $viewType]);
        }
        return ['saved' => $saved, 'errors' => $errors];
    }

    public static function deleteImage(int $id): int
    {
        $img = Database::one('SELECT * FROM product_images WHERE id = ?', [$id]);
        if (!$img) throw HttpException::notFound('Không tìm thấy ảnh');
        Database::exec('DELETE FROM product_images WHERE id = ?', [$id]);
        self::unlinkUpload($img['url']);
        return (int) $img['product_id'];
    }

    /** Save the drag-and-drop order of a product's images. */
    public static function reorderImages(int $productId, array $ids): void
    {
        $stmt = Database::pdo()->prepare('UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?');
        foreach (array_values($ids) as $i => $id) $stmt->execute([$i, (int) $id, $productId]);
    }

    public static function setImageType(int $id, string $viewType): int
    {
        if (!isset(self::VIEW_TYPES[$viewType])) throw HttpException::badRequest('Loại ảnh không hợp lệ');
        $img = Database::one('SELECT product_id FROM product_images WHERE id = ?', [$id]);
        if (!$img) throw HttpException::notFound('Không tìm thấy ảnh');
        Database::exec('UPDATE product_images SET view_type = ? WHERE id = ?', [$viewType, $id]);
        return (int) $img['product_id'];
    }

    // ---------------------------------------------------------------- duplicate & bulk

    /**
     * Copy a product as a hidden draft: same texts/prices, variants with stock 0 (new SKUs),
     * images copied as new files so deleting one product never removes the other's photos.
     */
    public static function duplicate(int $id): int
    {
        $p = Database::one('SELECT * FROM products WHERE id = ?', [$id]);
        if (!$p) throw HttpException::notFound('Không tìm thấy sản phẩm');

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $slug = $p['slug'] . '-ban-sao';
            for ($n = 2; Database::one('SELECT id FROM products WHERE slug = ?', [$slug]); $n++) $slug = $p['slug'] . '-ban-sao-' . $n;

            $copy = array_diff_key($p, array_flip(['id', 'created_at', 'updated_at']));
            $copy = array_merge($copy, ['slug' => $slug, 'name_vi' => $p['name_vi'] . ' (bản sao)', 'name_en' => $p['name_en'] . ' (copy)',
                                        'is_active' => 0, 'is_featured' => 0]);
            $newId = self::create($copy);

            foreach (self::variants($id) as $v) {
                $sku = preg_replace('/^CP\d{3}/', sprintf('CP%03d', $newId), $v['sku']);
                Database::exec('INSERT IGNORE INTO product_variants (product_id, size, fabric_id, color_vi, color_en, color_hex, stock, sku)
                                VALUES (?, ?, ?, ?, ?, ?, 0, ?)',
                               [$newId, $v['size'], $v['fabric_id'], $v['color_vi'], $v['color_en'], $v['color_hex'], $sku]);
            }

            $root = dirname(__DIR__, 2);
            foreach (self::images($id) as $img) {
                $url = $img['url'];
                if (str_starts_with($url, 'uploads/products/') && is_file("$root/$url")) {
                    $new = sprintf('uploads/products/%d-%s.%s', $newId, bin2hex(random_bytes(6)), pathinfo($url, PATHINFO_EXTENSION));
                    if (!copy("$root/$url", "$root/$new")) continue;
                    $url = $new;
                }
                Database::exec('INSERT INTO product_images (product_id, url, view_type, sort_order) VALUES (?, ?, ?, ?)',
                               [$newId, $url, $img['view_type'], $img['sort_order']]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Activity::log('product.create', "Nhân bản “{$p['name_vi']}” thành sản phẩm #$newId", 'product', $newId);
        return $newId;
    }

    /** Apply an action to several products. @return int affected count */
    public static function bulk(array $ids, string $action, array $params = []): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) throw HttpException::badRequest('Chưa chọn sản phẩm nào');
        $in = implode(',', $ids);
        $sql = match ($action) {
            'activate'   => "UPDATE products SET is_active = 1 WHERE id IN ($in)",
            'hide'       => "UPDATE products SET is_active = 0 WHERE id IN ($in)",
            'feature'    => "UPDATE products SET is_featured = 1 WHERE id IN ($in)",
            'unfeature'  => "UPDATE products SET is_featured = 0 WHERE id IN ($in)",
            'unsale'     => "UPDATE products SET sale_price = NULL, sale_starts_at = NULL, sale_ends_at = NULL WHERE id IN ($in)",
            'sale'       => 'sale',
            'delete'     => null,
            default      => throw HttpException::badRequest('Thao tác không hợp lệ'),
        };
        if ($sql === 'sale') {
            // X% off the base price, rounded down to the nearest 1.000 ₫; optional end date
            $pct = (int) ($params['percent'] ?? 0);
            if ($pct < 1 || $pct > 90) throw HttpException::badRequest('Phần trăm giảm từ 1 đến 90');
            $ends = self::datetime((string) ($params['ends_at'] ?? ''));
            if ($ends === 'invalid' || ($ends && strtotime($ends) <= time())) throw HttpException::badRequest('Ngày kết thúc phải ở tương lai');
            $n = Database::exec("UPDATE products SET sale_price = FLOOR(base_price * (100 - :p) / 100 / 1000) * 1000,
                                    sale_starts_at = NULL, sale_ends_at = :e WHERE id IN ($in)", ['p' => $pct, 'e' => $ends]);
        } elseif ($sql === null) {
            foreach ($ids as $id) self::delete($id);
            $n = count($ids);
        } else {
            $n = Database::exec($sql);
        }
        $labels = ['activate' => 'Bật bán', 'hide' => 'Ẩn', 'feature' => 'Đặt nổi bật', 'unfeature' => 'Bỏ nổi bật', 'delete' => 'Xoá',
                   'sale' => 'Giảm ' . (int) ($params['percent'] ?? 0) . '% cho', 'unsale' => 'Bỏ giảm giá'];
        Activity::log('product.bulk', $labels[$action] . ' ' . count($ids) . ' sản phẩm (#' . implode(', #', $ids) . ')', 'product', null);
        return $n;
    }

    private static function unlinkUpload(string $url): void
    {
        if (!str_starts_with($url, 'uploads/products/') || str_contains($url, '..')) return;
        $path = dirname(__DIR__, 2) . '/' . $url;
        if (is_file($path)) @unlink($path);
    }
}
