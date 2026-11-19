<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException};
use App\Support\Auth;

/**
 * Stock changes and their history. Every change goes through record() so /admin/kho can show
 * who changed what, why, and the stock level afterwards.
 */
final class Inventory
{
    /** reason => [label, badge classes, sign shown to staff] */
    public const REASONS = [
        'initial' => ['Số dư đầu',   'bg-line text-ink'],
        'import'  => ['Nhập hàng',   'bg-emerald-100 text-emerald-800'],
        'adjust'  => ['Kiểm kê',     'bg-sky-100 text-sky-800'],
        'damage'  => ['Hư hỏng/mất', 'bg-crimson/10 text-crimson'],
        'order'   => ['Bán (đơn)',   'bg-gold/20 text-gold-dark'],
        'cancel'  => ['Huỷ đơn',     'bg-violet-100 text-violet-800'],
    ];
    /** reasons staff can choose for a manual change */
    public const MANUAL = ['import', 'adjust', 'damage'];
    public const PER_PAGE = 30;

    /**
     * Log a change that has ALREADY been applied to product_variants (inside the caller's transaction).
     * $userId: null = current staff member; 0 = customer/system.
     */
    public static function record(int $variantId, int $change, string $reason, ?string $note = null, ?int $orderId = null, ?int $userId = null): void
    {
        $v = Database::one(
            "SELECT v.id, v.product_id, v.sku, v.stock, v.size, v.color_vi, p.name_vi, f.name_vi AS fabric
               FROM product_variants v JOIN products p ON p.id = v.product_id LEFT JOIN fabrics f ON f.id = v.fabric_id
              WHERE v.id = ?", [$variantId]);
        if (!$v) return;
        $userId = $userId === 0 ? null : ($userId ?? Auth::user()['id'] ?? null);
        Database::exec(
            'INSERT INTO stock_movements (variant_id, product_id, sku, product_name, variant_label, qty_change, stock_after, reason, note, order_id, user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$v['id'], $v['product_id'], $v['sku'], $v['name_vi'], self::label($v), $change, (int) $v['stock'],
             $reason, $note !== null ? mb_substr($note, 0, 255) : null, $orderId, $userId]);
    }

    /**
     * Manual change by staff. Either set an absolute level ($set) or apply a delta ($delta).
     * @return array{id:int, stock:int, change:int, product_id:int}
     */
    public static function adjust(int $variantId, ?int $set, ?int $delta, string $reason, ?string $note = null): array
    {
        if (!in_array($reason, self::MANUAL, true)) throw HttpException::badRequest('Lý do không hợp lệ', ['reason' => 'Chọn lý do']);
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $v = Database::one('SELECT id, product_id, stock, sku FROM product_variants WHERE id = ? FOR UPDATE', [$variantId]);
            if (!$v) throw HttpException::notFound('Không tìm thấy biến thể');
            $old = (int) $v['stock'];
            $new = $set ?? ($old + (int) $delta);
            if ($new < 0 || $new > 100000) throw HttpException::badRequest('Tồn kho phải từ 0 đến 100000', ['stock' => 'Tồn kho phải từ 0 đến 100000']);
            $change = $new - $old;
            if ($change !== 0) {
                Database::exec('UPDATE product_variants SET stock = ? WHERE id = ?', [$new, $variantId]);
                Database::exec('UPDATE products SET updated_at = NOW() WHERE id = ?', [$v['product_id']]);
                self::record($variantId, $change, $reason, $note);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        if ($change !== 0) {
            Activity::log('inventory.adjust', sprintf('%s %s%d → còn %d (%s)', $v['sku'], $change > 0 ? '+' : '', $change, $new,
                          self::REASONS[$reason][0]) . ($note ? " — $note" : ''), 'variant', $variantId);
        }
        return ['id' => $variantId, 'stock' => $new, 'change' => $change, 'product_id' => (int) $v['product_id']];
    }

    /** "Gấm · Đỏ son · Size M" */
    public static function label(array $v): string
    {
        return implode(' · ', array_filter([$v['fabric'] ?? null, $v['color_vi'] ?? null, ($v['size'] ?? 'FREE') === 'FREE' ? null : 'Size ' . $v['size']]));
    }

    // ---------------------------------------------------------------- lists

    /** All variants with stock, for the stock table. Filters: q, level (low|out), category. */
    public static function stockList(array $f): array
    {
        $low = low_stock();
        $where = ['1 = 1']; $p = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(p.name_vi LIKE :q1 OR v.sku LIKE :q2 OR v.color_vi LIKE :q3)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (($f['level'] ?? '') === 'low') $where[] = "v.stock > 0 AND v.stock <= $low";
        if (($f['level'] ?? '') === 'out') $where[] = 'v.stock = 0';
        $sql = 'FROM product_variants v JOIN products p ON p.id = v.product_id LEFT JOIN fabrics f ON f.id = v.fabric_id WHERE ' . implode(' AND ', $where);

        $total = (int) Database::one("SELECT COUNT(*) n $sql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $items = Database::all(
            "SELECT v.id, v.sku, v.size, v.color_vi, v.color_hex, v.stock, f.name_vi AS fabric, p.id AS product_id, p.name_vi, p.is_active,
                    (SELECT COALESCE(SUM(-m.qty_change), 0) FROM stock_movements m
                      WHERE m.variant_id = v.id AND m.reason = 'order' AND m.created_at >= NOW() - INTERVAL 30 DAY) AS sold_30d
             $sql ORDER BY v.stock = 0 DESC, v.stock <= $low DESC, p.name_vi, v.sku
             LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $p);
        $summary = Database::one("SELECT COUNT(*) variants, COALESCE(SUM(stock), 0) units, SUM(stock = 0) out_count,
                                         SUM(stock > 0 AND stock <= $low) low_count FROM product_variants");
        return compact('items', 'total', 'page', 'pages', 'summary');
    }

    /** Movement history. Filters: q, reason, variant, from, to. */
    public static function movements(array $f): array
    {
        $where = ['1 = 1']; $p = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(m.product_name LIKE :q1 OR m.sku LIKE :q2 OR m.note LIKE :q3)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (isset(self::REASONS[$f['reason'] ?? ''])) { $where[] = 'm.reason = :r'; $p['r'] = $f['reason']; }
        if (!empty($f['variant'])) { $where[] = 'm.variant_id = :v'; $p['v'] = (int) $f['variant']; }
        if (!empty($f['from'])) { $where[] = 'm.created_at >= :from'; $p['from'] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']))   { $where[] = 'm.created_at <= :to';   $p['to']   = $f['to'] . ' 23:59:59'; }
        $sql = 'FROM stock_movements m LEFT JOIN users u ON u.id = m.user_id LEFT JOIN orders o ON o.id = m.order_id WHERE ' . implode(' AND ', $where);

        $total = (int) Database::one("SELECT COUNT(*) n $sql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $items = Database::all("SELECT m.*, u.full_name AS user_name, o.order_code $sql ORDER BY m.id DESC
                                LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $p);
        return compact('items', 'total', 'page', 'pages');
    }
}
