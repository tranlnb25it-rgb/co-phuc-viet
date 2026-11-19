<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Customers, derived from orders (guest checkout: one customer = one phone number).
 * Spending never counts cancelled orders.
 */
final class CustomerService
{
    public const PER_PAGE = 20;
    public const VIP_FROM = 5_000_000;
    public const SEGMENTS = ['' => 'Tất cả', 'vip' => 'VIP (≥ 5 triệu)', 'repeat' => 'Mua từ 2 lần', 'new' => 'Mới (30 ngày)', 'custom' => 'Từng may đo'];
    public const SORTS = ['last' => 'Mua gần nhất', 'spent' => 'Chi tiêu nhiều nhất', 'orders' => 'Nhiều đơn nhất', 'first' => 'Khách mới nhất'];

    /** Aggregated per phone. Latest name/email/address come from the most recent order. */
    private static function base(): string
    {
        return "SELECT o.phone,
                       SUBSTRING_INDEX(GROUP_CONCAT(o.customer_name ORDER BY o.created_at DESC SEPARATOR '\\n'), '\\n', 1) AS name,
                       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(o.email, '') ORDER BY o.email IS NULL, o.created_at DESC SEPARATOR '\\n'), '\\n', 1) AS email,
                       SUBSTRING_INDEX(GROUP_CONCAT(o.shipping_address ORDER BY o.created_at DESC SEPARATOR '\\n'), '\\n', 1) AS address,
                       COUNT(*)                                                   AS orders,
                       SUM(o.status <> 'cancelled')                               AS valid_orders,
                       COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.total END), 0) AS spent,
                       MIN(o.created_at)                                          AS first_order,
                       MAX(o.created_at)                                          AS last_order,
                       (SELECT COUNT(*) FROM order_items i JOIN orders o2 ON o2.id = i.order_id
                         WHERE o2.phone = o.phone AND i.size = 'CUSTOM')          AS custom_items
                  FROM orders o GROUP BY o.phone";
    }

    public static function list(array $f): array
    {
        $having = ['1 = 1']; $p = [];
        if (($f['q'] ?? '') !== '') {
            $having[] = '(c.name LIKE :q1 OR c.phone LIKE :q2 OR c.email LIKE :q3)';
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        match ($f['segment'] ?? '') {
            'vip'    => $having[] = 'c.spent >= ' . self::VIP_FROM,
            'repeat' => $having[] = 'c.valid_orders >= 2',
            'new'    => $having[] = 'c.first_order >= NOW() - INTERVAL 30 DAY',
            'custom' => $having[] = 'c.custom_items > 0',
            default  => null,
        };
        $order = match ($f['sort'] ?? 'last') {
            'spent'  => 'c.spent DESC',
            'orders' => 'c.orders DESC, c.spent DESC',
            'first'  => 'c.first_order DESC',
            default  => 'c.last_order DESC',
        };
        $sql = 'FROM (' . self::base() . ') c WHERE ' . implode(' AND ', $having);

        $total = (int) Database::one("SELECT COUNT(*) n $sql", $p)['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) ($f['page'] ?? 1)));
        $items = Database::all("SELECT c.* $sql ORDER BY $order LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), $p);
        $summary = Database::one("SELECT COUNT(*) customers, SUM(c.valid_orders >= 2) repeaters, SUM(c.spent >= " . self::VIP_FROM . ") vip,
                                         COALESCE(AVG(c.spent), 0) avg_spent FROM (" . self::base() . ") c");
        return compact('items', 'total', 'page', 'pages', 'summary');
    }

    /** One customer: profile, orders, saved measurements, addresses used. */
    public static function find(string $phone): ?array
    {
        $c = Database::one('SELECT c.* FROM (' . self::base() . ') c WHERE c.phone = ?', [$phone]);
        if (!$c) return null;
        $c['order_list'] = Database::all(
            "SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS item_count
               FROM orders o WHERE o.phone = ? ORDER BY o.created_at DESC", [$phone]);
        $c['measurements'] = Database::all(
            "SELECT i.product_name, i.custom_measurements, o.order_code, o.created_at
               FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE o.phone = ? AND i.size = 'CUSTOM' AND i.custom_measurements IS NOT NULL
              ORDER BY o.created_at DESC", [$phone]);
        $c['addresses'] = array_column(Database::all(
            'SELECT shipping_address, MAX(created_at) last FROM orders WHERE phone = ? GROUP BY shipping_address ORDER BY last DESC', [$phone]),
            'shipping_address');
        $c['favorites'] = Database::all(
            "SELECT i.product_name, SUM(i.quantity) qty FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE o.phone = ? AND o.status <> 'cancelled' GROUP BY i.product_name ORDER BY qty DESC LIMIT 5", [$phone]);
        return $c;
    }
}
