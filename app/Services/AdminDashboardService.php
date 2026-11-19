<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Numbers for the admin dashboard over a date range, compared with the previous range of equal length.
 * Revenue never includes cancelled orders.
 */
final class AdminDashboardService
{
    public const RANGES = [7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày'];

    public static function build(int $days): array
    {
        $days = isset(self::RANGES[$days]) ? $days : 30;
        $end   = new \DateTimeImmutable('today');
        $start = $end->modify('-' . ($days - 1) . ' days');
        $prevEnd   = $start->modify('-1 day');
        $prevStart = $prevEnd->modify('-' . ($days - 1) . ' days');

        $cur  = self::kpis($start, $end);
        $prev = self::kpis($prevStart, $prevEnd);

        return [
            'days'     => $days,
            'start'    => $start,
            'end'      => $end,
            'kpis'     => $cur,
            'deltas'   => array_map(fn($k) => self::delta($cur[$k], $prev[$k]), array_combine(array_keys($cur), array_keys($cur))),
            'series'   => self::series($start, $end, $days > 31 ? 'week' : 'day'),
            'statuses' => self::statusBreakdown($start, $end),
            'top'      => self::topProducts($start, $end),
            'low_stock'=> self::lowStock(),
            'recent'   => Database::all('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT 6'),
            'pending'  => (int) Database::one("SELECT COUNT(*) n FROM orders WHERE status = 'pending'")['n'],
        ];
    }

    private static function range(\DateTimeImmutable $a, \DateTimeImmutable $b): array
    {
        return [$a->format('Y-m-d 00:00:00'), $b->format('Y-m-d 23:59:59')];
    }

    /** revenue, orders, aov, custom_share (% of items that were made to measure). */
    private static function kpis(\DateTimeImmutable $a, \DateTimeImmutable $b): array
    {
        [$from, $to] = self::range($a, $b);
        $o = Database::one(
            "SELECT COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS revenue,
                    COUNT(*)                                                       AS orders,
                    SUM(status <> 'cancelled')                                     AS valid_orders,
                    SUM(status = 'cancelled')                                      AS cancelled
               FROM orders WHERE created_at BETWEEN ? AND ?", [$from, $to]);
        $i = Database::one(
            "SELECT COALESCE(SUM(i.quantity), 0) AS items, COALESCE(SUM(CASE WHEN i.size = 'CUSTOM' THEN i.quantity END), 0) AS custom
               FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?", [$from, $to]);

        $valid = (int) $o['valid_orders'];
        return [
            'revenue'      => (int) $o['revenue'],
            'orders'       => (int) $o['orders'],
            'aov'          => $valid ? (int) round($o['revenue'] / $valid) : 0,
            'custom_share' => (int) $i['items'] ? round($i['custom'] / $i['items'] * 100, 1) : 0.0,
        ];
    }

    /** % change, or null when there is nothing to compare with. */
    private static function delta(int|float $cur, int|float $prev): ?float
    {
        if ($prev == 0) return null;
        return round(($cur - $prev) / $prev * 100, 1);
    }

    /** Revenue + order count per day (or per week for long ranges), zero-filled. */
    private static function series(\DateTimeImmutable $a, \DateTimeImmutable $b, string $bucket): array
    {
        [$from, $to] = self::range($a, $b);
        $rows = Database::all(
            "SELECT DATE(created_at) d, SUM(total) revenue, COUNT(*) orders
               FROM orders WHERE status <> 'cancelled' AND created_at BETWEEN ? AND ?
              GROUP BY DATE(created_at)", [$from, $to]);
        $byDay = array_column($rows, null, 'd');

        $points = [];
        for ($d = $a; $d <= $b; $d = $d->modify($bucket === 'week' ? '+7 days' : '+1 day')) {
            $last = $bucket === 'week' ? min($d->modify('+6 days'), $b) : $d;
            $rev = 0; $cnt = 0;
            for ($x = $d; $x <= $last; $x = $x->modify('+1 day')) {
                $k = $x->format('Y-m-d');
                $rev += (int) ($byDay[$k]['revenue'] ?? 0);
                $cnt += (int) ($byDay[$k]['orders'] ?? 0);
            }
            $points[] = [
                'label'  => $bucket === 'week' ? $d->format('d/m') : $d->format('d/m'),
                'title'  => $bucket === 'week' ? 'Tuần ' . $d->format('d/m') . ' – ' . $last->format('d/m') : self::weekday($d) . ', ' . $d->format('d/m/Y'),
                'value'  => $rev,
                'orders' => $cnt,
            ];
        }
        return ['bucket' => $bucket, 'points' => $points];
    }

    private static function weekday(\DateTimeImmutable $d): string
    {
        return ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][(int) $d->format('w')];
    }

    private static function statusBreakdown(\DateTimeImmutable $a, \DateTimeImmutable $b): array
    {
        [$from, $to] = self::range($a, $b);
        $rows = Database::all('SELECT status, COUNT(*) n FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status', [$from, $to]);
        $out = array_fill_keys(array_keys(AdminOrderService::STATUSES), 0);
        foreach ($rows as $r) $out[$r['status']] = (int) $r['n'];
        return $out;
    }

    /** Best sellers by revenue (snapshot names, so deleted products still count). */
    private static function topProducts(\DateTimeImmutable $a, \DateTimeImmutable $b, int $limit = 5): array
    {
        [$from, $to] = self::range($a, $b);
        return Database::all(
            "SELECT MAX(i.product_id) AS product_id, i.product_name AS name,
                    SUM(i.quantity) AS qty, SUM(i.quantity * i.unit_price) AS revenue,
                    SUM(CASE WHEN i.size = 'CUSTOM' THEN i.quantity ELSE 0 END) AS custom_qty
               FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
              GROUP BY i.product_name ORDER BY revenue DESC, qty DESC LIMIT $limit", [$from, $to]);
    }

    private static function lowStock(): array
    {
        return Database::all(
            'SELECT v.id, v.size, v.color_vi, v.stock, f.name_vi AS fabric, p.id AS product_id, p.name_vi
               FROM product_variants v JOIN products p ON p.id = v.product_id LEFT JOIN fabrics f ON f.id = v.fabric_id
              WHERE v.stock <= ' . low_stock() . ' AND p.is_active = 1
              ORDER BY v.stock, p.name_vi LIMIT 8');
    }
}
