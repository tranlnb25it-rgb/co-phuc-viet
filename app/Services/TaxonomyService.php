<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};

/** Admin CRUD for categories, dynasties (eras) and fabrics. */
final class TaxonomyService
{
    public const TYPES = [
        'categories' => 'Danh mục',
        'dynasties'  => 'Triều đại',
        'fabrics'    => 'Chất liệu',
    ];

    public static function all(): array
    {
        return [
            'categories' => Database::all(
                'SELECT c.*, p.name_vi AS parent_name,
                        (SELECT COUNT(*) FROM products x WHERE x.category_id = c.id) AS product_count,
                        (SELECT COUNT(*) FROM categories k WHERE k.parent_id = c.id) AS child_count
                   FROM categories c LEFT JOIN categories p ON p.id = c.parent_id
                  ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.id'),
            'dynasties' => Database::all(
                'SELECT d.*, (SELECT COUNT(*) FROM products x WHERE x.dynasty_id = d.id) AS product_count
                   FROM dynasties d ORDER BY d.sort_order, d.id'),
            'fabrics' => Database::all(
                'SELECT f.*, (SELECT COUNT(*) FROM product_variants v WHERE v.fabric_id = f.id) AS variant_count
                   FROM fabrics f ORDER BY f.id'),
        ];
    }

    /** Create ($id null) or update. @return int id */
    public static function save(string $type, ?int $id, array $in): int
    {
        if (!isset(self::TYPES[$type])) throw HttpException::badRequest('Loại không hợp lệ');
        $str = fn(string $k, int $max) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $max);
        $e = [];

        $d = ['name_vi' => $str('name_vi', 120), 'name_en' => $str('name_en', 120)];
        if (mb_strlen($d['name_vi']) < 2) $e['name_vi'] = 'Nhập tên';
        if ($d['name_en'] === '') $d['name_en'] = $d['name_vi'];
        $d['slug'] = slugify($str('slug', 120) ?: $d['name_vi']);
        if ($d['slug'] === '') $e['slug'] = 'Slug không hợp lệ';
        elseif (Database::one("SELECT id FROM $type WHERE slug = ? AND id <> ?", [$d['slug'], $id ?? 0])) $e['slug'] = 'Slug đã tồn tại';

        switch ($type) {
            case 'categories':
                $d['parent_id'] = ($in['parent_id'] ?? '') === '' ? null : (int) $in['parent_id'];
                $d['description_vi'] = $str('description_vi', 1000) ?: null;
                $d['description_en'] = $str('description_en', 1000) ?: null;
                $d['sort_order'] = (int) ($in['sort_order'] ?? 0);
                if ($d['parent_id'] !== null) {
                    $parent = Database::one('SELECT id, parent_id FROM categories WHERE id = ?', [$d['parent_id']]);
                    if (!$parent || $parent['parent_id'] !== null || $d['parent_id'] === $id) $e['parent_id'] = 'Chỉ chọn danh mục cấp 1 (khác chính nó)';
                    elseif ($id && Database::one('SELECT id FROM categories WHERE parent_id = ? LIMIT 1', [$id])) $e['parent_id'] = 'Danh mục này đang có danh mục con';
                }
                break;
            case 'dynasties':
                $d['period'] = $str('period', 40) ?: null;
                $d['sort_order'] = (int) ($in['sort_order'] ?? 0);
                break;
            case 'fabrics':
                $d['price_modifier'] = (int) preg_replace('/\D/', '', (string) ($in['price_modifier'] ?? '0'));
                if ($d['price_modifier'] > 100_000_000) $e['price_modifier'] = 'Phụ phí quá lớn';
                break;
        }
        if ($e) throw HttpException::badRequest('Vui lòng kiểm tra lại', $e);

        if ($id) {
            if (!Database::one("SELECT id FROM $type WHERE id = ?", [$id])) throw HttpException::notFound();
            $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($d)));
            Database::exec("UPDATE $type SET $set WHERE id = :id", $d + ['id' => $id]);
            Activity::log('catalog.update', self::TYPES[$type] . " “{$d['name_vi']}” đã được sửa", $type, $id);
        } else {
            $cols = array_keys($d);
            Database::exec("INSERT INTO $type (" . implode(',', $cols) . ') VALUES (:' . implode(',:', $cols) . ')', $d);
            $id = (int) Database::pdo()->lastInsertId();
            Activity::log('catalog.create', 'Thêm ' . mb_strtolower(self::TYPES[$type]) . " “{$d['name_vi']}”", $type, $id);
        }
        return $id;
    }

    /** Delete only when nothing uses it (products, variants, child categories). */
    public static function delete(string $type, int $id): void
    {
        if (!isset(self::TYPES[$type])) throw HttpException::badRequest('Loại không hợp lệ');
        $row = Database::one("SELECT name_vi FROM $type WHERE id = ?", [$id]);
        if (!$row) throw HttpException::notFound();
        $used = match ($type) {
            'categories' => (int) Database::one('SELECT (SELECT COUNT(*) FROM products WHERE category_id = ?) + (SELECT COUNT(*) FROM categories WHERE parent_id = ?) n', [$id, $id])['n'],
            'dynasties'  => (int) Database::one('SELECT COUNT(*) n FROM products WHERE dynasty_id = ?', [$id])['n'],
            'fabrics'    => (int) Database::one('SELECT COUNT(*) n FROM product_variants WHERE fabric_id = ?', [$id])['n'],
        };
        if ($used) throw HttpException::badRequest("Không thể xoá: đang được dùng bởi $used sản phẩm/biến thể/danh mục con. Hãy chuyển chúng sang mục khác trước.");
        Database::exec("DELETE FROM $type WHERE id = ?", [$id]);
        Activity::log('catalog.delete', 'Xoá ' . mb_strtolower(self::TYPES[$type]) . " “{$row['name_vi']}”", $type, $id);
    }
}
